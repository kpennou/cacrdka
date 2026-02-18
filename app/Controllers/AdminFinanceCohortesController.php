<?php
class AdminFinanceCohortesController extends Controller
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

    $cohortes = $pdo->query("
      SELECT c.id, c.libelle, c.date_debut, c.date_fin, ct.montant_total, ct.date_limite_paiement
      FROM cohortes c
      LEFT JOIN cohorte_tarifs ct ON ct.cohorte_id=c.id
      ORDER BY c.id DESC
      LIMIT 300
    ")->fetchAll();

    $this->view('admin_finance/cohortes', [
      'cohortes' => $cohortes,
      'error' => Session::flash('error'),
      'success' => Session::flash('success'),
    ]);
  }

  public function save(): void {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $this->csrfOrFail('/admin/finance/cohortes');

    $cohorte_id = (int)($_POST['cohorte_id'] ?? 0);
    $montant_total = trim((string)($_POST['montant_total'] ?? ''));
    $date_limite = trim((string)($_POST['date_limite_paiement'] ?? ''));

    if ($cohorte_id <= 0 || $montant_total === '') {
      Session::flash('error', 'Cohorte et montant total requis.');
      redirect('/admin/finance/cohortes');
    }

    $montant = (float)str_replace(',', '.', $montant_total);
    if ($montant <= 0) {
      Session::flash('error', 'Montant invalide.');
      redirect('/admin/finance/cohortes');
    }

    $pdo = DB::pdo();

    // Cohorte existe ?
    $c = $pdo->prepare("SELECT id FROM cohortes WHERE id=?");
    $c->execute([$cohorte_id]);
    if (!$c->fetch()) {
      Session::flash('error', 'Cohorte introuvable.');
      redirect('/admin/finance/cohortes');
    }

    // Upsert
    $exists = $pdo->prepare("SELECT id FROM cohorte_tarifs WHERE cohorte_id=?");
    $exists->execute([$cohorte_id]);
    $row = $exists->fetch();

    if ($row) {
      $upd = $pdo->prepare("
        UPDATE cohorte_tarifs
        SET montant_total=?, date_limite_paiement=?
        WHERE cohorte_id=?
      ");
      $upd->execute([$montant, ($date_limite!==''?$date_limite:null), $cohorte_id]);
    } else {
      $ins = $pdo->prepare("
        INSERT INTO cohorte_tarifs (cohorte_id, montant_total, date_limite_paiement)
        VALUES (?,?,?)
      ");
      $ins->execute([$cohorte_id, $montant, ($date_limite!==''?$date_limite:null)]);
    }

    Session::flash('success', 'Tarif cohorte enregistré.');
    redirect('/admin/finance/cohortes');
  }
}
