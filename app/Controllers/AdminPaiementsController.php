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
    $q = trim((string)($_GET['q'] ?? ''));

    // Cohortes pour filtre
    $cohortes = $pdo->query("SELECT id, libelle FROM cohortes ORDER BY id DESC LIMIT 200")->fetchAll();

    // Liste inscriptions + finance (snapshot optionnel)
    $sql = "
      SELECT
        i.id AS inscription_id,
        i.cohorte_id,
        c.libelle AS cohorte,

        a.id AS apprenant_id,
        a.nom,
        a.prenoms,
        a.telephone,

        f.montant_total,
        f.bourse_montant,
        f.montant_net,

        COALESCE((SELECT SUM(p.montant) FROM paiements p WHERE p.inscription_id=i.id),0) AS total_paye,

        CASE
          WHEN f.inscription_id IS NULL THEN NULL
          ELSE (f.montant_net - COALESCE((SELECT SUM(p.montant) FROM paiements p WHERE p.inscription_id=i.id),0))
        END AS reste_a_payer,

        f.date_limite_paiement,

        CASE
          WHEN f.inscription_id IS NULL THEN 0
          WHEN f.date_limite_paiement IS NOT NULL AND CURDATE() > f.date_limite_paiement
               AND (f.montant_net - COALESCE((SELECT SUM(p.montant) FROM paiements p WHERE p.inscription_id=i.id),0)) > 0
          THEN 1 ELSE 0 END AS en_retard,

        COALESCE(f.statut,'SANS_SNAPSHOT') AS statut_finance

      FROM inscriptions i
      JOIN cohortes c ON c.id=i.cohorte_id
      JOIN apprenants a ON a.id=i.apprenant_id
      LEFT JOIN inscription_finance f ON f.inscription_id=i.id
    ";

    $where = [];
    $params = [];

    if ($cohorte_id > 0) { $where[] = "i.cohorte_id=?"; $params[] = $cohorte_id; }

    if ($q !== '') {
      $where[] = "(a.nom LIKE ? OR a.prenoms LIKE ? OR a.telephone LIKE ?)";
      $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
    }

    if ($where) $sql .= " WHERE " . implode(" AND ", $where);

    // Trier: retards en haut, puis reste décroissant (NULL à la fin), puis id desc
    $sql .= " ORDER BY en_retard DESC,
                     (CASE WHEN reste_a_payer IS NULL THEN -1 ELSE reste_a_payer END) DESC,
                     inscription_id DESC
              LIMIT 300";

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

    // Détail finance (nécessite snapshot -> vue)
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
      Session::flash('error', 'Inscription finance introuvable (snapshot manquant ?). Paramétrez le tarif cohorte puis générez les snapshots manquants.');
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
      Session::flash('error', 'Impossible: snapshot finance absent (inscription_finance). Paramétrez le tarif cohorte puis générez les snapshots.');
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

    // ajouter saveBourse()
    public function saveBourse(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail('/admin/finance/paiements');

    $pdo = DB::pdo();

    $inscription_id = (int)($_POST['inscription_id'] ?? 0);
    $bourse_raw = trim((string)($_POST['bourse_montant'] ?? ''));

    if ($inscription_id <= 0 || $bourse_raw === '') {
      Session::flash('error', 'Inscription et bourse requis.');
      redirect('/admin/finance/paiements/voir?id='.$inscription_id);
    }

    $bourse = (float)str_replace(',', '.', $bourse_raw);
    if ($bourse < 0) $bourse = 0;

    // Charger finance snapshot
    $st = $pdo->prepare("SELECT montant_total FROM inscription_finance WHERE inscription_id=? AND statut!='ANNULE' LIMIT 1");
    $st->execute([$inscription_id]);
    $fin = $st->fetch();

    if (!$fin) {
      Session::flash('error', 'Snapshot finance introuvable (inscription_finance).');
      redirect('/admin/finance/paiements');
    }

    $montantTotal = (float)$fin['montant_total'];
    if ($bourse > $montantTotal) $bourse = $montantTotal;

    $montantNet = $montantTotal - $bourse;

    // Appliquer bourse + net
    $upd = $pdo->prepare("
      UPDATE inscription_finance
      SET bourse_montant=?, montant_net=?
      WHERE inscription_id=?
      LIMIT 1
    ");
    $upd->execute([$bourse, $montantNet, $inscription_id]);

    // Recalcul statut selon paiements déjà faits
    $sum = $pdo->prepare("SELECT COALESCE(SUM(montant),0) AS total FROM paiements WHERE inscription_id=?");
    $sum->execute([$inscription_id]);
    $totalPaye = (float)$sum->fetch()['total'];

    if ($totalPaye >= $montantNet) {
      $pdo->prepare("UPDATE inscription_finance SET statut='SOLDE' WHERE inscription_id=?")->execute([$inscription_id]);
    } else {
      $pdo->prepare("UPDATE inscription_finance SET statut='EN_COURS' WHERE inscription_id=?")->execute([$inscription_id]);
    }

    Session::flash('success', 'Bourse enregistrée et montant net recalculé.');
    redirect('/admin/finance/paiements/voir?id='.$inscription_id);
  }

}
