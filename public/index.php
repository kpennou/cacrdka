<?php
declare(strict_types=1);

require __DIR__ . '/../app/Core/Session.php';
require __DIR__ . '/../app/Core/Router.php';
require __DIR__ . '/../app/Core/Controller.php';
require __DIR__ . '/../app/Core/DB.php';
require __DIR__ . '/../app/Core/Csrf.php';
require __DIR__ . '/../app/Core/Auth.php';
require __DIR__ . '/../app/Core/Upload.php';
require __DIR__ . '/../app/Core/helpers.php';

require __DIR__ . '/../app/Controllers/AuthController.php';
require __DIR__ . '/../app/Controllers/DashboardController.php';
require __DIR__ . '/../app/Controllers/PreinscriptionController.php';
require __DIR__ . '/../app/Controllers/CandidatController.php';
require __DIR__ . '/../app/Controllers/AdminVivierController.php';
require __DIR__ . '/../app/Controllers/HealthController.php';
require __DIR__ . '/../app/Controllers/AdminPieceController.php';

require __DIR__ . '/../app/Controllers/FormateurPreinscriptionController.php';
require __DIR__ . '/../app/Controllers/FormateurCandidatController.php';
require __DIR__ . '/../app/Controllers/AdminVivierFormateursController.php';
require __DIR__ . '/../app/Controllers/AdminFormateurPieceController.php';

require __DIR__ . '/../app/Controllers/AdminEquipeFormateursController.php';

require __DIR__ . '/../app/Controllers/AdminMatieresController.php';
require __DIR__ . '/../app/Controllers/AdminAffectationsController.php';

require __DIR__ . '/../app/Controllers/AdminEvaluationsController.php';

require __DIR__ . '/../app/Controllers/AdminFinanceCohortesController.php';
require __DIR__ . '/../app/Controllers/AdminPaiementsController.php';



Session::start();

/** Chargement .env */
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
  foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    [$k,$v] = explode('=', $line, 2);
    putenv(trim($k) . '=' . trim($v));
  }
}

$router = new Router();

$auth = new AuthController();
$dash = new DashboardController();
$pre  = new PreinscriptionController();
$cand = new CandidatController();
$adm  = new AdminVivierController();
$health = new HealthController();
$adminPiece = new AdminPieceController();

$formPre = new FormateurPreinscriptionController();
$formCand = new FormateurCandidatController();
$adminForm = new AdminVivierFormateursController();
$adminFormPiece = new AdminFormateurPieceController();

$adminEquipe = new AdminEquipeFormateursController();
$adminMatieres = new AdminMatieresController();
$adminAffect = new AdminAffectationsController();

$adminEval = new AdminEvaluationsController();

//Finance
$adminFinanceCohortes = new AdminFinanceCohortesController();
$adminPaiements = new AdminPaiementsController();



$router->get('/', fn() => redirect('/login'));

$router->get('/health', fn() => $health->index());

$router->get('/login', fn() => $auth->showLogin());
$router->post('/login', fn() => $auth->login());
$router->get('/logout', fn() => $auth->logout());

$router->get('/directeur', fn() => $dash->directeur());

$router->get('/preinscription', fn() => $pre->createForm());
$router->post('/preinscription', fn() => $pre->store());

$router->get('/candidat/acces', fn() => $cand->accessForm());
$router->post('/candidat/acces', fn() => $cand->accessSubmit());
$router->get('/candidat/dossier', fn() => $cand->dossier());
$router->post('/candidat/upload', fn() => $cand->uploadPiece());
$router->post('/candidat/soumettre', fn() => $cand->soumettre());

$router->get('/admin/vivier-apprenants', fn() => $adm->index());
$router->get('/admin/vivier-apprenants/voir', fn() => $adm->voir());
$router->get('/admin/piece/download', fn() => $adminPiece->download());

$router->post('/admin/vivier-apprenants/selectionner', fn() => $adm->selectionner());
$router->post('/admin/vivier-apprenants/rejeter', fn() => $adm->rejeter());
$router->post('/admin/vivier-apprenants/convertir', fn() => $adm->convertir());


// --- Formateurs (candidat) ---
$router->get('/formateur/preinscription', fn() => $formPre->createForm());
$router->post('/formateur/preinscription', fn() => $formPre->store());

$router->get('/formateur/acces', fn() => $formCand->accessForm());
$router->post('/formateur/acces', fn() => $formCand->accessSubmit());
$router->get('/formateur/dossier', fn() => $formCand->dossier());
$router->post('/formateur/upload', fn() => $formCand->uploadPiece());
$router->post('/formateur/soumettre', fn() => $formCand->soumettre());

// --- Admin : vivier formateurs ---
$router->get('/admin/vivier-formateurs', fn() => $adminForm->index());
$router->get('/admin/vivier-formateurs/voir', fn() => $adminForm->voir());
$router->post('/admin/vivier-formateurs/retenir', fn() => $adminForm->retenir());
$router->post('/admin/vivier-formateurs/rejeter', fn() => $adminForm->rejeter());
$router->post('/admin/vivier-formateurs/convertir', fn() => $adminForm->convertir());

// --- Admin : download pièce formateur ---
$router->get('/admin/formateur-piece/download', fn() => $adminFormPiece->download());

$router->get('/admin/formateurs', fn() => $adminEquipe->index());
$router->post('/admin/formateurs/toggle', fn() => $adminEquipe->toggleStatut());

// Matières
$router->get('/admin/matieres', fn() => $adminMatieres->index());
$router->post('/admin/matieres/create', fn() => $adminMatieres->create());
$router->post('/admin/matieres/toggle', fn() => $adminMatieres->toggle());

// Affectations
$router->get('/admin/affectations', fn() => $adminAffect->index());
$router->post('/admin/affectations/create', fn() => $adminAffect->create());
$router->post('/admin/affectations/toggle', fn() => $adminAffect->toggle());
$router->post('/admin/affectations/delete', fn() => $adminAffect->delete());

//Evaluations
$router->get('/admin/evaluations', fn() => $adminEval->index());
$router->post('/admin/evaluations/create', fn() => $adminEval->create());
$router->get('/admin/evaluations/notes', fn() => $adminEval->notesForm());
$router->post('/admin/evaluations/notes/save', fn() => $adminEval->notesSave());
$router->post('/admin/evaluations/toggle', fn() => $adminEval->toggleStatut());

// Finance - Tarifs cohorte
$router->get('/admin/finance/cohortes', fn() => $adminFinanceCohortes->index());
$router->post('/admin/finance/cohortes/save', fn() => $adminFinanceCohortes->save());

// Finance - Paiements
$router->get('/admin/finance/paiements', fn() => $adminPaiements->index());
$router->get('/admin/finance/paiements/voir', fn() => $adminPaiements->voir());
$router->post('/admin/finance/paiements/ajouter', fn() => $adminPaiements->ajouter());

//V1 Finance UI sans snapsht
$router->post('/admin/finance/snapshot/rebuild', fn() => $adminFinanceCohortes->rebuildSnapshots());


$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
