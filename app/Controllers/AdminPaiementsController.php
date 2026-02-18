<?php
class AdminPaiementsController extends Controller
{
  private function csrfOrFail(string $redir): void {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
      Session::flash('error', 'CSRF: session expirée.');
      redirect($redir);
    }
  }

  public function index(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $pdo = DB::pdo();

    $cohorte_id = (int)($_GET['cohorte_id'] ?? 0);
    $q = trim((string)($_GET['q'] ?? '')); // recherche nom/tel/matricule éventuel

    // Cohortes pour filtre
    $cohortes = $pdo->query("SELECT id, libelle FROM cohortes ORDER BY id DESC LIMIT 200")->fetchAll();

    // Liste inscriptions finance (avec reste)
    $sql = "
      SELECT
        vf.inscription_id,
        vf.cohorte_id,
        vf.cohorte,
        vf.montant_total,
        vf.bourse_montant,
        vf.montant_net,
        vf.total_paye,
        vf.reste_a_payer,
        vf.date_limite_paiement,
        vf.en_retard,
        vf.statut,
        a.nom,
        a.prenoms,
        a.telephone
      FROM vw_inscriptions_finance vf
      JOIN apprenants a ON a.id = vf.apprenant_id
    ";
    $where = [];
    $params = [];

    if ($cohorte_id > 0) { $where[] = "vf.cohorte_id=?"; $params[] = $cohorte_id; }
    if ($q !== '') {
      $where[] = "(a.nom LIKE ? OR a.prenoms LIKE ? OR a.telephone LIKE ?)";
      $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
    }
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);

    $sql .= " ORDER BY vf.en_retard DESC, vf.reste_a_payer DESC, vf.inscription_id DESC LIMIT 300";

    $st = $pdo->prepare($sql);
    $st->execute($params);

    $this->view('admin_finance/paiements_index', [
      'rows' => $st->fetchAll(),
      'cohortes' => $cohortes,
      'filters' => ['cohorte_id'=>$cohorte_id, 'q'=>$q],
      'error' => Session::flash('error'),
      'success' => Session::flash('success'),
    ]);
  }

  public function voir(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $pdo = DB::pdo();

    $inscription_id = (int)($_GET['id'] ?? 0);
    if ($inscription_id <= 0) redirect('/admin/finance/paiements');

    // Détail finance
    $st = $pdo->prepare("
      SELECT
        vf.*,
        a.nom, a.prenoms, a.telephone
      FROM vw_inscriptions_finance vf
      JOIN apprenants a ON a.id = vf.apprenant_id
      WHERE vf.inscription_id=?
      LIMIT 1
    ");
    $st->execute([$inscription_id]);
    $fiche = $st->fetch();
    if (!$fiche) {
      Session::flash('error', 'Inscription finance introuvable (snapshot manquant ?).');
      redirect('/admin/finance/paiements');
    }

    // Liste paiements
    $p = $pdo->prepare("SELECT * FROM paiements WHERE inscription_id=? ORDER BY date_paiement DESC, id DESC");
    $p->execute([$inscription_id]);

    $this->view('admin_finance/paiements_voir', [
      'fiche' => $fiche,
      'paiements' => $p->fetchAll(),
      'error' => Session::flash('error'),
      'success' => Session::flash('success'),
    ]);
  }

  public function ajouter(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail('/admin/finance/paiements');

    $pdo = DB::pdo();

    $inscription_id = (int)($_POST['inscription_id'] ?? 0);
    $montant_raw = trim((string)($_POST['montant'] ?? ''));
    $date_paiement = trim((string)($_POST['date_paiement'] ?? ''));
    $mode = trim((string)($_POST['mode'] ?? 'MOBILE_MONEY'));
    $reference = trim((string)($_POST['reference'] ?? ''));
    $commentaire = trim((string)($_POST['commentaire'] ?? ''));

    if ($inscription_id<=0 || $montant_raw==='' || $date_paiement==='') {
      Session::flash('error', 'Inscription, montant et date requis.');
      redirect('/admin/finance/paiements/voir?id='.$inscription_id);
    }

    $montant = (float)str_replace(',', '.', $montant_raw);
    if ($montant <= 0) {
      Session::flash('error', 'Montant invalide.');
      redirect('/admin/finance/paiements/voir?id='.$inscription_id);
    }

    $allowedModes = ['ESPECES','MOBILE_MONEY','VIREMENT','CHEQUE','AUTRE'];
    if (!in_array($mode, $allowedModes, true)) $mode = 'MOBILE_MONEY';

    // Vérifie snapshot finance
    $f = $pdo->prepare("SELECT montant_net FROM inscription_finance WHERE inscription_id=? AND statut!='ANNULE' LIMIT 1");
    $f->execute([$inscription_id]);
    $fin = $f->fetch();
    if (!$fin) {
      Session::flash('error', 'Impossible: snapshot finance absent (inscription_finance).');
      redirect('/admin/finance/paiements');
    }

    // Insert paiement
    $ins = $pdo->prepare("
      INSERT INTO paiements (inscription_id, montant, date_paiement, mode, reference, commentaire)
      VALUES (?,?,?,?,?,?)
    ");
    $ins->execute([
      $inscription_id,
      $montant,
      $date_paiement,
      $mode,
      ($reference!==''?$reference:null),
      ($commentaire!==''?$commentaire:null),
    ]);

    // Mettre à jour statut SOLDE si payé >= net
    $sum = $pdo->prepare("SELECT COALESCE(SUM(montant),0) AS total FROM paiements WHERE inscription_id=?");
    $sum->execute([$inscription_id]);
    $totalPaye = (float)$sum->fetch()['total'];

    $montantNet = (float)$fin['montant_net'];
    if ($totalPaye >= $montantNet) {
      $pdo->prepare("UPDATE inscription_finance SET statut='SOLDE' WHERE inscription_id=?")->execute([$inscription_id]);
    } else {
      $pdo->prepare("UPDATE inscription_finance SET statut='EN_COURS' WHERE inscription_id=?")->execute([$inscription_id]);
    }

    Session::flash('success', 'Paiement ajouté.');
    redirect('/admin/finance/paiements/voir?id='.$inscription_id);
  }
}
