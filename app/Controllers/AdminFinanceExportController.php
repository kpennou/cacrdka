<?php
class AdminFinanceExportController extends Controller
{
  public function exportCohortesCsv(): void
  {
    Auth::requireRole(['DIRECTEUR','ADMIN']);
    $pdo = DB::pdo();

    // Optionnel: limiter par cohorte_id ou statut
    $statut = trim((string)($_GET['statut'] ?? '')); // PLANIFIEE, EN_COURS, CLOTUREE

    $sql = "SELECT * FROM vw_kpi_finance_par_cohorte";
    $params = [];

    if ($statut !== '') {
      $sql .= " WHERE statut=?";
      $params[] = $statut;
    }

    $sql .= " ORDER BY cohorte_id DESC";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    // Nom de fichier
    $filename = 'finance_cohortes_' . date('Ymd_His') . '.csv';

    // Headers CSV (UTF-8)
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // BOM UTF-8 pour Excel
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    if ($out === false) {
      // fallback simple
      echo "Erreur export CSV";
      exit;
    }

    // En-têtes
    fputcsv($out, [
      'cohorte_id',
      'cohorte',
      'statut',
      'tarif_cohorte',
      'date_limite_paiement',
      'nb_inscrits',
      'nb_snapshots',
      'total_a_encaisser',
      'total_encaisse',
      'total_restant',
      'total_bourses',
      'nb_retards'
    ], ';');

    foreach ($rows as $r) {
      fputcsv($out, [
        $r['cohorte_id'],
        $r['cohorte'],
        $r['statut'],
        $r['tarif_cohorte'],
        $r['date_limite_paiement'],
        $r['nb_inscrits'],
        $r['nb_snapshots'],
        $r['total_a_encaisser'],
        $r['total_encaisse'],
        $r['total_restant'],
        $r['total_bourses'],
        $r['nb_retards'],
      ], ';');
    }

    fclose($out);
    exit;
  }

    // Journal caisse
    public function journalCaisse(): void
        {
        Auth::requireRole(['DIRECTEUR','ADMIN']);
        $pdo = DB::pdo();

        // par défaut : mois en cours
        $date_debut = trim((string)($_GET['date_debut'] ?? date('Y-m-01')));
        $date_fin   = trim((string)($_GET['date_fin'] ?? date('Y-m-t')));
        $cohorte_id = (int)($_GET['cohorte_id'] ?? 0);

        $cohortes = $pdo->query("SELECT id, libelle FROM cohortes ORDER BY id DESC LIMIT 200")->fetchAll();

        $sql = "SELECT * FROM vw_paiements_journal WHERE date_paiement BETWEEN ? AND ?";
        $params = [$date_debut, $date_fin];

        if ($cohorte_id > 0) {
            $sql .= " AND cohorte_id=?";
            $params[] = $cohorte_id;
        }

        $sql .= " ORDER BY date_paiement DESC, paiement_id DESC LIMIT 1000";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();

        // Totaux
        $tot = $pdo->prepare("
            SELECT
            COALESCE(SUM(montant),0) AS total,
            COALESCE(SUM(CASE WHEN mode='ESPECES' THEN montant ELSE 0 END),0) AS total_especes,
            COALESCE(SUM(CASE WHEN mode='MOBILE_MONEY' THEN montant ELSE 0 END),0) AS total_mobile,
            COALESCE(SUM(CASE WHEN mode='VIREMENT' THEN montant ELSE 0 END),0) AS total_virement,
            COALESCE(SUM(CASE WHEN mode='CHEQUE' THEN montant ELSE 0 END),0) AS total_cheque,
            COALESCE(SUM(CASE WHEN mode='AUTRE' THEN montant ELSE 0 END),0) AS total_autre
            FROM vw_paiements_journal
            WHERE date_paiement BETWEEN ? AND ?
            " . ($cohorte_id>0 ? " AND cohorte_id=?" : "")
        );
        $totParams = [$date_debut, $date_fin];
        if ($cohorte_id>0) $totParams[] = $cohorte_id;
        $tot->execute($totParams);
        $totaux = $tot->fetch() ?: [
            'total'=>0,'total_especes'=>0,'total_mobile'=>0,'total_virement'=>0,'total_cheque'=>0,'total_autre'=>0
        ];

        //Contrôle de caisse (écart)
        $montant_compte_raw = trim((string)($_GET['montant_compte'] ?? ''));
        $montant_compte = null;
        if ($montant_compte_raw !== '') {
        $montant_compte = (float)str_replace(',', '.', $montant_compte_raw);
        }
        $ecart = null;
        if ($montant_compte !== null) {
        $ecart = $montant_compte - (float)($totaux['total'] ?? 0);
        }
        // Fin Contrôle de caisse (écart)

        // Totaux par jour
        $sqlJour = "
        SELECT
            date_paiement,
            COALESCE(SUM(montant),0) AS total,
            COALESCE(SUM(CASE WHEN mode='ESPECES' THEN montant ELSE 0 END),0) AS total_especes,
            COALESCE(SUM(CASE WHEN mode='MOBILE_MONEY' THEN montant ELSE 0 END),0) AS total_mobile,
            COALESCE(SUM(CASE WHEN mode='VIREMENT' THEN montant ELSE 0 END),0) AS total_virement,
            COALESCE(SUM(CASE WHEN mode='CHEQUE' THEN montant ELSE 0 END),0) AS total_cheque,
            COALESCE(SUM(CASE WHEN mode='AUTRE' THEN montant ELSE 0 END),0) AS total_autre,
            COUNT(*) AS nb_paiements
        FROM vw_paiements_journal
        WHERE date_paiement BETWEEN ? AND ?
        ";
        $jourParams = [$date_debut, $date_fin];

        if ($cohorte_id > 0) {
        $sqlJour .= " AND cohorte_id=?";
        $jourParams[] = $cohorte_id;
        }

        $sqlJour .= " GROUP BY date_paiement ORDER BY date_paiement DESC";

        $stJour = $pdo->prepare($sqlJour);
        $stJour->execute($jourParams);
        $totauxParJour = $stJour->fetchAll();


        $this->view('admin_finance/journal', [
            'rows' => $rows,
            'totaux' => $totaux,
            'cohortes' => $cohortes,
            'totauxParJour' => $totauxParJour,
            'controle' => [
            'montant_compte' => $montant_compte,
            'ecart' => $ecart
            ],
            'filters' => ['date_debut'=>$date_debut,'date_fin'=>$date_fin,'cohorte_id'=>$cohorte_id, 'montant_compte'=>$montant_compte_raw],
            'error' => Session::flash('error'),
            'success' => Session::flash('success'),
        ]);
    }

        // Export CSV journal

        public function exportJournalCsv(): void
        {
        Auth::requireRole(['DIRECTEUR','ADMIN']);
        $pdo = DB::pdo();

        $date_debut = trim((string)($_GET['date_debut'] ?? date('Y-m-01')));
        $date_fin   = trim((string)($_GET['date_fin'] ?? date('Y-m-t')));
        $cohorte_id = (int)($_GET['cohorte_id'] ?? 0);

        $sql = "SELECT * FROM vw_paiements_journal WHERE date_paiement BETWEEN ? AND ?";
        $params = [$date_debut, $date_fin];

        if ($cohorte_id > 0) {
            $sql .= " AND cohorte_id=?";
            $params[] = $cohorte_id;
        }

        $sql .= " ORDER BY date_paiement DESC, paiement_id DESC";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();

        $filename = 'journal_caisse_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF"; // BOM UTF-8

        $out = fopen('php://output', 'w');

        fputcsv($out, [
            'paiement_id',
            'date_paiement',
            'montant',
            'mode',
            'reference',
            'commentaire',
            'cohorte_id',
            'cohorte',
            'inscription_id',
            'apprenant_id',
            'matricule',
            'apprenant',
            'telephone'
        ], ';');

        foreach ($rows as $r) {
            fputcsv($out, [
            $r['paiement_id'],
            $r['date_paiement'],
            $r['montant'],
            $r['mode'],
            $r['reference'],
            $r['commentaire'],
            $r['cohorte_id'],
            $r['cohorte'],
            $r['inscription_id'],
            $r['apprenant_id'],
            $r['matricule'],
            $r['apprenant'],
            $r['telephone'],
            ], ';');
        }

        fclose($out);
        exit;
        }

}
