<?php

namespace App\Controller;

use App\Entity\Disponibilite;
use App\Entity\RendezVous;
use App\Entity\TypeRendezVous;
use App\Entity\User;
use App\Form\RendezVousType;
use App\Repository\DisponibiliteRepository;
use App\Repository\RendezVousRepository;
use App\Repository\TypeRendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/rendez-vous')]
class RendezVousController extends AbstractController
{
    // ✅ 1. CALENDRIER BUNDLE (le plus spécifique)
    #[Route('/calendrier-bundle', name: 'app_rendez_vous_calendrier_bundle')]
    public function calendrierBundle(TypeRendezVousRepository $typeRepo): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        
        $types = $typeRepo->findAll();
        
        return $this->render('rendez_vous/calendrier_bundle.html.twig', [
            'types' => $types
        ]);
    }

    // ✅ 2. INDEX
    #[Route('/', name: 'app_rendez_vous_index', methods: ['GET'])]
    public function index(
        Request $request, 
        RendezVousRepository $repo,
        PaginatorInterface $paginator
    ): Response 
    {
        // Pour les patients, le bouton "Rdv" doit ouvrir directement l'interface de réservation
        if ($this->getUser() instanceof \App\Entity\User) {
            $roles = $this->getUser()->getRoles();
            $isPatient = in_array('ROLE_PATIENT', $roles, true);
            $isStaff = in_array('ROLE_PSYCHOLOGUE', $roles, true) || in_array('ROLE_ADMIN', $roles, true);
            if ($isPatient && !$isStaff) {
                return $this->redirectToRoute('app_rendez_vous_new');
            }
        }

        $search = $request->query->get('q');
        $sort = $request->query->get('sort', 'date');
        $search = is_string($search) || $search === null ? $search : null;
        $sort = is_string($sort) || $sort === null ? $sort : null;
        
        $queryBuilder = $repo->findBySearchAndSortQueryBuilder($search, $sort);
        
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            3
        );

        return $this->render('rendez_vous/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
            'sort' => $sort
        ]);
    }
    
    // ✅ 3. NOUVEAU
    #[Route('/new', name: 'app_rendez_vous_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        RendezVousRepository $repo,
        DisponibiliteRepository $disponibiliteRepo,
    ): Response 
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->syncAppointmentTypesFromLegacyTable($em);

        $rdv = new RendezVous();
        
        if ($this->getUser() instanceof \App\Entity\User) {
            $rdv->setPatient($this->getUser());
            $nom = trim((string) $this->getUser()->getNom());
            $prenom = trim((string) $this->getUser()->getPrenom());
            $full = trim($nom . ' ' . $prenom);
            if ($full !== '') {
                $rdv->setNomPatient($full);
            }
        }
        
        $form = $this->createForm(RendezVousType::class, $rdv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $err) {
                $this->addFlash('error', $err->getMessage());
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser() instanceof User) {
                $rdv->setPatient($this->getUser());
            }

            /** @var User|null $psy */
            $psy = $form->get('psychologue')->getData();
            if (!$psy instanceof User) {
                $this->addFlash('error', 'Psychologue invalide.');
                return $this->redirectToRoute('app_rendez_vous_new');
            }

            /** @var \DateTimeInterface|null $jour */
            $jour = $form->get('jour')->getData();
            $creneau = (string) $form->get('creneau')->getData();
            if (!$jour instanceof \DateTimeInterface || preg_match('/^\d{2}:\d{2}$/', $creneau) !== 1) {
                $this->addFlash('error', 'Veuillez choisir une date et un créneau valides.');
                return $this->redirectToRoute('app_rendez_vous_new');
            }

            $day = \DateTimeImmutable::createFromInterface($jour);
            $availableSlots = $this->computeAvailableSlotsForDate(
                $psy,
                $jour->format('Y-m-d'),
                $repo,
                $disponibiliteRepo
            );
            if (!in_array($creneau, $availableSlots, true)) {
                $this->addFlash('error', 'Ce créneau n’est plus disponible. Veuillez en choisir un autre.');
                return $this->redirectToRoute('app_rendez_vous_new');
            }

            $dispo = $disponibiliteRepo->findOneLibreForPsychologueDateHeure($psy, $day, $creneau);
            if (!$dispo instanceof Disponibilite) {
                $this->addFlash('error', 'Créneau introuvable.');
                return $this->redirectToRoute('app_rendez_vous_new');
            }
            if ($repo->countActiveForDisponibilite($dispo) > 0) {
                $this->addFlash('error', 'Ce créneau vient d’être réservé.');
                return $this->redirectToRoute('app_rendez_vous_new');
            }

            $patientNote = trim((string) $form->get('notePatient')->getData());
            $lat = $form->get('latitude')->getData();
            $lng = $form->get('longitude')->getData();

            $rdv->setDisponibilite($dispo);
            $rdv->setStatut(RendezVous::STATUT_EN_ATTENTE);
            $rdv->setNotesPatient($patientNote !== '' ? $patientNote : null);
            if (is_numeric($lat) && is_numeric($lng)) {
                $rdv->setLatitude((float) $lat);
                $rdv->setLongitude((float) $lng);
                $rdv->setAdresse(sprintf('Localisation patient (%s, %s)', (string) $lat, (string) $lng));
            }
            $u = $this->getUser();
            if ($u instanceof User) {
                $rdv->setAge($this->computePatientAge($u));
            }

            $em->persist($rdv);
            $em->flush();

            $this->addFlash('success', 'Demande enregistrée dans vos rendez-vous. Elle est en attente d’acceptation ou de refus par le psychologue.');
            return $this->redirectToRoute('app_rendez_vous_new');
        }

        // « Mes demandes » : lignes SQL `rendez_vous` pour ce patient.
        $statutListe = (string) $request->query->get('statut', 'tous');
        $statutListe = in_array($statutListe, ['tous', 'en_attente', 'acceptee', 'rejetee'], true) ? $statutListe : 'tous';

        $patient = $this->getUser();
        /** @var list<array<string, mixed>> $demandes */
        $demandes = [];
        if ($patient instanceof User) {
            $mapFilter = ['en_attente' => 'en_attente', 'acceptee' => 'accepte', 'rejetee' => 'rejete'];

            $candidates = [];
            foreach ($repo->findHistoriqueByPatient($patient) as $r) {
                $decision = RendezVous::normalizeStatut($r->getStatut());
                $ts = $r->getDate() ? $r->getDate()->getTimestamp() : 0;
                $candidates[] = [
                    'kind' => 'appointment',
                    'ts' => $ts,
                    'rdv' => $r,
                    'decision' => $decision,
                ];
            }

            usort($candidates, static fn(array $a, array $b): int => (($b['ts'] ?? 0) <=> ($a['ts'] ?? 0)));

            foreach ($candidates as $row) {
                $decision = (string) ($row['decision'] ?? '');
                if ($statutListe !== 'tous' && $decision !== ($mapFilter[$statutListe] ?? '')) {
                    continue;
                }
                $demandes[] = [
                    'kind' => 'appointment',
                    'rdv' => $row['rdv'],
                    'decision' => $decision,
                ];
            }
        }

        $availabilityMatrix = $this->buildAvailabilityMatrixForReservation($em, $repo, $disponibiliteRepo);
        // Toujours un objet JSON (jamais []) pour que le JS puisse indexer par id psychologue.
        $matrixForJson = $availabilityMatrix === [] ? new \stdClass() : $availabilityMatrix;

        return $this->render('rendez_vous/new.html.twig', [
            'form' => $form->createView(),
            'rdv' => $rdv,
            'demandes' => $demandes,
            'statut' => $statutListe,
            'availability_matrix_json' => json_encode(
                $matrixForJson,
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
        ]);
    }

    /**
     * Construit la matrice à partir de la table `disponibilite` (SQL direct = même source que la BD).
     *
     * @return array<string, array{dates: list<string>, slotsByDate: array<string, list<string>>}>
     */
    private function buildAvailabilityMatrixForReservation(
        EntityManagerInterface $em,
        RendezVousRepository $repo,
        DisponibiliteRepository $disponibiliteRepo,
    ): array {
        $conn = $em->getConnection();
        $rows = $conn->fetchAllAssociative(
            'SELECT DISTINCT psychologue_id, `date` FROM disponibilite
             WHERE est_libre = 1 AND psychologue_id IS NOT NULL AND `date` >= CURDATE()
             ORDER BY psychologue_id ASC, `date` ASC'
        );

        /** @var array<string, list<string>> $byPsy */
        $byPsy = [];
        foreach ($rows as $row) {
            $pid = (string) (int) ($row['psychologue_id'] ?? 0);
            if ($pid === '0') {
                continue;
            }
            $rawDate = $row['date'] ?? null;
            if ($rawDate instanceof \DateTimeInterface) {
                $dateStr = $rawDate->format('Y-m-d');
            } elseif (is_string($rawDate)) {
                $dateStr = substr($rawDate, 0, 10);
            } else {
                continue;
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr) !== 1) {
                continue;
            }
            $byPsy[$pid] ??= [];
            if (!in_array($dateStr, $byPsy[$pid], true)) {
                $byPsy[$pid][] = $dateStr;
            }
        }

        $matrix = [];
        foreach ($byPsy as $pid => $dates) {
            $psy = $em->getRepository(User::class)->find((int) $pid);
            if (!$psy instanceof User) {
                continue;
            }
            sort($dates);
            $slotsByDate = [];
            foreach ($dates as $dateStr) {
                $slotsByDate[$dateStr] = $this->computeAvailableSlotsForDate(
                    $psy,
                    $dateStr,
                    $repo,
                    $disponibiliteRepo
                );
            }
            $matrix[$pid] = [
                'dates' => $dates,
                'slotsByDate' => $slotsByDate,
            ];
        }

        return $matrix;
    }

    #[Route('/available-slots', name: 'app_rendez_vous_available_slots', methods: ['GET'])]
    public function availableSlots(
        Request $request,
        RendezVousRepository $repo,
        DisponibiliteRepository $disponibiliteRepo,
        EntityManagerInterface $em,
    ): JsonResponse
    {
        $psyId = $request->query->getInt('psy_id', 0);
        $psyName = trim((string) $request->query->get('psy', ''));
        $date = (string) $request->query->get('date', '');

        if (($psyId <= 0 && $psyName === '') || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return $this->json(['slots' => []]);
        }

        $psy = $psyId > 0 ? $em->getRepository(User::class)->find($psyId) : $this->findPsychologueByLabel($psyName, $em);
        if (!$psy instanceof User) {
            return $this->json(['slots' => []]);
        }

        $slots = $this->computeAvailableSlotsForDate($psy, $date, $repo, $disponibiliteRepo);

        return $this->json(['slots' => $slots]);
    }

    #[Route('/available-dates', name: 'app_rendez_vous_available_dates', methods: ['GET'])]
    public function availableDates(
        Request $request,
        DisponibiliteRepository $disponibiliteRepo,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $psyId = $request->query->getInt('psy_id', 0);
        $psyName = trim((string) $request->query->get('psy', ''));
        if ($psyId <= 0 && $psyName === '') {
            return $this->json(['dates' => []]);
        }

        $psy = $psyId > 0 ? $em->getRepository(User::class)->find($psyId) : $this->findPsychologueByLabel($psyName, $em);
        if (!$psy instanceof User) {
            return $this->json(['dates' => []]);
        }

        return $this->json([
            'dates' => $disponibiliteRepo->findAvailableDatesByPsychologue($psy),
        ]);
    }

    private function findPsychologueByLabel(string $label, EntityManagerInterface $em): ?User
    {
        $label = mb_strtolower(trim($label));
        if ($label === '') {
            return null;
        }

        /** @var list<User> $candidates */
        $candidates = $em->getRepository(User::class)->createQueryBuilder('u')
            ->getQuery()
            ->getResult();

        foreach ($candidates as $candidate) {
            $full = mb_strtolower(trim(((string) $candidate->getNom()) . ' ' . ((string) $candidate->getPrenom())));
            $email = mb_strtolower(trim((string) $candidate->getEmail()));
            if ($label === $full || $label === $email) {
                return $candidate;
            }
        }

        return null;
    }

    private function syncAppointmentTypesFromLegacyTable(EntityManagerInterface $em): void
    {
        // Les types sont lus/écrits dans la table SQL `type_rendez_vous` (mapping Doctrine).
    }

    private function computePatientAge(User $patient): ?int
    {
        $dn = $patient->getDateNaissance();
        if (!$dn instanceof \DateTimeInterface) {
            return null;
        }
        $birth = \DateTimeImmutable::createFromMutable(\DateTime::createFromInterface($dn));
        $now = new \DateTimeImmutable('today');

        return $birth->diff($now)->y;
    }

    /**
     * @return list<string>
     */
    private function computeAvailableSlotsForDate(
        User $psy,
        string $date,
        RendezVousRepository $rdvRepo,
        DisponibiliteRepository $disponibiliteRepo,
    ): array {
        $available = [];
        $day = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if (!$day) {
            return [];
        }
        $slots = $disponibiliteRepo->findByPsychologueAndDate($psy, $day);
        foreach ($slots as $slot) {
            if (!$slot instanceof Disponibilite || !$slot->isLibre()) {
                continue;
            }
            $start = $slot->getHeureDebut()?->format('H:i') ?? '';
            if (preg_match('/^\d{2}:\d{2}$/', $start) !== 1) {
                continue;
            }
            $available[$start] = true;
        }

        foreach ($rdvRepo->findBookedHeuresForPsychologueOnDate($psy, $day) as $hm) {
            unset($available[$hm]);
        }

        $out = array_keys($available);
        sort($out);
        return array_values($out);
    }

    // ✅ 4. HISTORIQUE
    #[Route('/historique', name: 'app_rendez_vous_historique')]
    public function historique(RendezVousRepository $repo): Response
    {
        $patient = $this->getUser();
        if (!$patient instanceof \App\Entity\User || !in_array('ROLE_PATIENT', $patient->getRoles(), true)) {
            throw $this->createAccessDeniedException('Accès réservé aux patients');
        }

        $rendezVous = $repo->findHistoriqueByPatient($patient);

        return $this->render('rendez_vous/historique.html.twig', [
            'rendez_vous' => $rendezVous
        ]);
    }

    // ✅ 5. STATISTIQUES
    #[Route('/statistiques/mois', name: 'app_rendez_vous_stats_mois')]
    public function statsMois(RendezVousRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $stats = $repo->getStatsParMois();

        return $this->render('rendez_vous/stats_mois.html.twig', [
            'stats' => $stats
        ]);
    }

    // ✅ 6. SHOW (route avec paramètre, en dernier)
    #[Route('/{id}', name: 'app_rendez_vous_show', methods: ['GET'])]
    public function show(?RendezVous $rdv): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        
        if (!$rdv) {
            $this->addFlash('error', 'Ce rendez-vous n\'existe pas ou a été supprimé.');
            return $this->redirectToRoute('app_rendez_vous_index');
        }
        
        return $this->render('rendez_vous/show.html.twig', [
            'rdv' => $rdv
        ]);
    }
}
