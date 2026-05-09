<?php

namespace App\Controller;

use App\Entity\AuditLog;
use App\Entity\Disponibilite;
use App\Entity\RendezVous;
use App\Entity\User;
use App\Repository\DisponibiliteRepository;
use App\Repository\RendezVousRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/psychologue/rdv')]
final class PsychologueRdvController extends AbstractController
{
    private function assertPsyArea(): void
    {
        if (!$this->isGranted('ROLE_PSYCHOLOGUE') && !$this->isGranted('ROLE_PSY') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
    }

    #[Route('', name: 'psychologue_rdv_manage', methods: ['GET'])]
    public function index(
        Request $request,
        RendezVousRepository $rdvRepo,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPsyArea();

        $psy = $this->getUser();
        if (!$psy instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $psyId = $psy->getId();
        if ($psyId === null) {
            throw $this->createAccessDeniedException();
        }

        /** @var list<RendezVous> $rdvs */
        $rdvs = $rdvRepo->createQueryBuilder('r')
            ->join('r.disponibilite', 'd')
            ->addSelect('d')
            ->andWhere('IDENTITY(d.psychologue) = :pid')
            ->setParameter('pid', $psyId)
            ->orderBy('d.date', 'DESC')
            ->addOrderBy('d.heureDebut', 'DESC')
            ->getQuery()
            ->getResult();

        $view = [];
        foreach ($rdvs as $rdv) {
            $id = $rdv->getId();
            if ($id === null) {
                continue;
            }
            $full = trim((string) ($rdv->getNomPatient() ?? ''));
            $parts = $full !== '' ? preg_split('/\s+/u', $full, 2) : [];
            $nomRow = ($parts[0] ?? '') !== '' ? $parts[0] : '—';
            $prenomRow = $parts[1] ?? null;

            $view[] = [
                'id' => $id,
                'nom' => $nomRow,
                'prenom' => $prenomRow,
                'type' => $rdv->getType()?->getLibelle(),
                'date' => $rdv->getDate(),
                'statut' => RendezVous::normalizeStatut($rdv->getStatut()),
                'patient_note' => $rdv->getNotesPatient(),
                'psy_note' => $rdv->getNotesPsychologue(),
            ];
        }

        return $this->render('psychologue/rdv_manage.html.twig', [
            'rows' => $view,
        ]);
    }

    #[Route('/{id}/decision', name: 'psychologue_rdv_decision', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function decision(
        int $id,
        Request $request,
        RendezVousRepository $rdvRepo,
        EntityManagerInterface $em,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPsyArea();

        $psy = $this->getUser();
        if (!$psy instanceof User || $psy->getId() === null) {
            throw $this->createAccessDeniedException();
        }

        $rdv = $rdvRepo->find($id);
        if (!$rdv) {
            $this->addFlash('error', 'Rendez-vous introuvable.');
            return $this->redirectToRoute('psychologue_rdv_manage');
        }

        if ($rdv->getDisponibilite()?->getPsychologue()?->getId() !== $psy->getId()) {
            $this->addFlash('error', 'Ce rendez-vous ne figure pas à votre agenda.');
            return $this->redirectToRoute('psychologue_rdv_manage');
        }

        if (RendezVous::normalizeStatut($rdv->getStatut()) !== 'en_attente') {
            $this->addFlash('warning', 'Cette demande a déjà été acceptée ou refusée.');
            return $this->redirectToRoute('psychologue_rdv_manage');
        }

        $action = (string) $request->request->get('action', '');
        if (!in_array($action, ['accept', 'reject'], true)) {
            $this->addFlash('error', 'Action invalide.');
            return $this->redirectToRoute('psychologue_rdv_manage');
        }

        if (!$this->isCsrfTokenValid('rdv_decision_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('psychologue_rdv_manage');
        }

        $rdv->setStatut($action === 'accept' ? RendezVous::STATUT_ACCEPTE : RendezVous::STATUT_REJETE);

        $audit = new AuditLog();
        $audit->setAction($action === 'accept' ? 'rdv_accept' : 'rdv_reject');
        $audit->setEntityType('rendez_vous');
        $audit->setEntityId($id);
        $audit->setUser($this->getUser() instanceof User ? $this->getUser() : null);
        $audit->assignCreator($this->getUser() instanceof User ? $this->getUser() : null);
        $audit->assignUpdater($this->getUser() instanceof User ? $this->getUser() : null);
        $audit->setIp((string) $request->getClientIp());
        $audit->setDetails(json_encode([
            'status' => $action === 'accept' ? 'accepte' : 'rejete',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $em->persist($audit);
        $em->flush();

        $this->addFlash('success', $action === 'accept' ? 'Rendez-vous accepté.' : 'Rendez-vous rejeté.');
        return $this->redirectToRoute('psychologue_rdv_manage');
    }

    #[Route('/{id}/note', name: 'psychologue_rdv_note', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function saveNote(int $id, Request $request, EntityManagerInterface $em, RendezVousRepository $rdvRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPsyArea();

        $psy = $this->getUser();
        if (!$psy instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $rdv = $rdvRepo->find($id);
        if (!$rdv instanceof RendezVous || $rdv->getDisponibilite()?->getPsychologue()?->getId() !== $psy->getId()) {
            $this->addFlash('error', 'Rendez-vous introuvable ou inaccessible.');
            return $this->redirectToRoute('psychologue_rdv_manage');
        }

        if (!$this->isCsrfTokenValid('rdv_note_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('psychologue_rdv_manage');
        }

        $note = trim((string) $request->request->get('psy_note', ''));

        $rdv->setNotesPsychologue($note !== '' ? $note : null);

        $audit = new AuditLog();
        $audit->setAction('rdv_psy_note');
        $audit->setEntityType('rendez_vous');
        $audit->setEntityId($id);
        $audit->setUser($this->getUser() instanceof User ? $this->getUser() : null);
        $audit->assignCreator($this->getUser() instanceof User ? $this->getUser() : null);
        $audit->assignUpdater($this->getUser() instanceof User ? $this->getUser() : null);
        $audit->setIp((string) $request->getClientIp());
        $audit->setDetails(json_encode([
            'psy_note' => $note !== '' ? $note : null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $em->persist($audit);
        $em->flush();

        $this->addFlash('success', 'Note enregistrée.');
        return $this->redirectToRoute('psychologue_rdv_manage');
    }

    #[Route('/disponibilites', name: 'psychologue_disponibilites', methods: ['GET'])]
    public function disponibilites(
        Request $request,
        DisponibiliteRepository $disponibiliteRepo,
        RendezVousRepository $rdvRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPsyArea();

        $psy = $this->getUser();
        if (!$psy instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $slots = $disponibiliteRepo->findByPsychologue($psy);

        $today = new \DateTimeImmutable('today');
        $appointments = $rdvRepo->createQueryBuilder('r')
            ->join('r.disponibilite', 'd')
            ->addSelect('d')
            ->andWhere('IDENTITY(d.psychologue) = :pid')
            ->andWhere('d.date >= :start')
            ->setParameter('pid', $psy->getId())
            ->setParameter('start', $today, Types::DATE_MUTABLE)
            ->orderBy('d.date', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();

        $reservedByDate = [];
        foreach ($appointments as $rdv) {
            if (!$rdv instanceof RendezVous || !in_array($rdv->getStatut(), RendezVousRepository::statutsOccupantCreneau(), true)) {
                continue;
            }
            $dt = $rdv->getDate();
            if ($dt instanceof \DateTimeInterface) {
                $reservedByDate[$dt->format('Y-m-d H:i')] = true;
            }
        }

        $rows = [];
        foreach ($slots as $slot) {
            if (!$slot instanceof Disponibilite) {
                continue;
            }
            $dateObj = $slot->getDate();
            $startObj = $dateObj && $slot->getHeureDebut()
                ? \DateTimeImmutable::createFromFormat('Y-m-d H:i', $dateObj->format('Y-m-d') . ' ' . $slot->getHeureDebut()?->format('H:i'))
                : null;
            $isReserved = $startObj ? isset($reservedByDate[$startObj->format('Y-m-d H:i')]) : false;
            $isLibre = $slot->isLibre();

            $rows[] = [
                'id' => $slot->getId(),
                'date' => $dateObj?->format('d/m/Y'),
                'date_iso' => $dateObj?->format('Y-m-d') ?? '',
                'start' => $slot->getHeureDebut()?->format('H:i') ?? '',
                'end' => $slot->getHeureFin()?->format('H:i') ?? '',
                'reservable' => $isLibre,
                'reserved' => $isReserved,
                'status_label' => $isReserved ? 'réservé' : ($isLibre ? 'libre' : 'indisponible'),
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            $ka = (($a['date_iso'] ?? '') . ' ' . ($a['start'] ?? ''));
            $kb = (($b['date_iso'] ?? '') . ' ' . ($b['start'] ?? ''));
            return $ka <=> $kb;
        });

        $total = count($rows);
        $reservedCount = count(array_filter($rows, static fn(array $r): bool => ($r['reserved'] ?? false) === true));
        $freeCount = count(array_filter($rows, static fn(array $r): bool => ($r['status_label'] ?? '') === 'libre'));

        return $this->render('psychologue/disponibilites.html.twig', [
            'rows' => $rows,
            'stats' => [
                'total' => $total,
                'libres' => $freeCount,
                'reserves' => $reservedCount,
            ],
            'selectedId' => (string) $request->query->get('slot', ''),
        ]);
    }

    #[Route('/disponibilites/add', name: 'psychologue_disponibilites_add', methods: ['POST'])]
    public function disponibilitesAdd(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPsyArea();

        if (!$this->isCsrfTokenValid('psy_dispo_add', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('psychologue_disponibilites');
        }

        $payload = $this->extractAvailabilityPayload($request);
        if (!$payload['valid']) {
            $this->addFlash('error', (string) $payload['error']);
            return $this->redirectToRoute('psychologue_disponibilites');
        }

        $psy = $this->getUser();
        if (!$psy instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $dispo = new Disponibilite();
        $dispo->setPsychologue($psy);
        $dispo->setDate(new \DateTimeImmutable((string) $payload['date']));
        $dispo->setHeureDebut(\DateTimeImmutable::createFromFormat('H:i', (string) $payload['start']) ?: null);
        $dispo->setHeureFin(\DateTimeImmutable::createFromFormat('H:i', (string) $payload['end']) ?: null);
        $dispo->setLibre((bool) $payload['reservable']);
        $em->persist($dispo);
        $em->flush();

        $this->addFlash('success', 'Créneau ajouté avec succès.');
        return $this->redirectToRoute('psychologue_disponibilites', ['slot' => $dispo->getId()]);
    }

    #[Route('/disponibilites/edit', name: 'psychologue_disponibilites_edit', methods: ['POST'])]
    public function disponibilitesEdit(
        Request $request,
        EntityManagerInterface $em,
        DisponibiliteRepository $disponibiliteRepo,
        RendezVousRepository $rdvRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPsyArea();

        if (!$this->isCsrfTokenValid('psy_dispo_edit', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('psychologue_disponibilites');
        }

        $slotId = $request->request->getInt('slot_id', 0);
        if ($slotId <= 0) {
            $this->addFlash('error', 'Veuillez sélectionner un créneau à modifier.');
            return $this->redirectToRoute('psychologue_disponibilites');
        }

        $psy = $this->getUser();
        if (!$psy instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $slot = $disponibiliteRepo->find($slotId);
        if (!$slot instanceof Disponibilite || $slot->getPsychologue()?->getId() !== $psy->getId()) {
            $this->addFlash('error', 'Créneau introuvable.');
            return $this->redirectToRoute('psychologue_disponibilites');
        }
        if ($this->isDisponibiliteReserved($slot, $rdvRepo)) {
            $this->addFlash('error', 'Ce créneau est déjà réservé et ne peut pas être modifié.');
            return $this->redirectToRoute('psychologue_disponibilites');
        }

        $payload = $this->extractAvailabilityPayload($request);
        if (!$payload['valid']) {
            $this->addFlash('error', (string) $payload['error']);
            return $this->redirectToRoute('psychologue_disponibilites');
        }

        $slot->setDate(new \DateTimeImmutable((string) $payload['date']));
        $slot->setHeureDebut(\DateTimeImmutable::createFromFormat('H:i', (string) $payload['start']) ?: null);
        $slot->setHeureFin(\DateTimeImmutable::createFromFormat('H:i', (string) $payload['end']) ?: null);
        $slot->setLibre((bool) $payload['reservable']);
        $em->flush();

        $this->addFlash('success', 'Créneau modifié avec succès.');
        return $this->redirectToRoute('psychologue_disponibilites', ['slot' => $slot->getId()]);
    }

    #[Route('/disponibilites/delete', name: 'psychologue_disponibilites_delete', methods: ['POST'])]
    public function disponibilitesDelete(
        Request $request,
        EntityManagerInterface $em,
        DisponibiliteRepository $disponibiliteRepo,
        RendezVousRepository $rdvRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->assertPsyArea();

        if (!$this->isCsrfTokenValid('psy_dispo_delete', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('psychologue_disponibilites');
        }

        $slotId = $request->request->getInt('slot_id', 0);
        if ($slotId <= 0) {
            $this->addFlash('error', 'Veuillez sélectionner un créneau à supprimer.');
            return $this->redirectToRoute('psychologue_disponibilites');
        }

        $psy = $this->getUser();
        if (!$psy instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $slot = $disponibiliteRepo->find($slotId);
        if (!$slot instanceof Disponibilite || $slot->getPsychologue()?->getId() !== $psy->getId()) {
            $this->addFlash('error', 'Créneau introuvable.');
            return $this->redirectToRoute('psychologue_disponibilites');
        }
        if ($this->isDisponibiliteReserved($slot, $rdvRepo)) {
            $this->addFlash('error', 'Ce créneau est déjà réservé et ne peut pas être supprimé.');
            return $this->redirectToRoute('psychologue_disponibilites');
        }

        $em->remove($slot);
        $em->flush();

        $this->addFlash('success', 'Créneau supprimé.');
        return $this->redirectToRoute('psychologue_disponibilites');
    }

    /**
     * @return array{valid: bool, error?: string, date?: string, start?: string, end?: string, reservable?: bool}
     */
    private function extractAvailabilityPayload(Request $request): array
    {
        $date = trim((string) $request->request->get('date', ''));
        $start = trim((string) $request->request->get('start', ''));
        $end = trim((string) $request->request->get('end', ''));
        $reservable = $request->request->getBoolean('reservable', true);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return ['valid' => false, 'error' => 'Date invalide.'];
        }
        if (preg_match('/^\d{2}:\d{2}$/', $start) !== 1 || preg_match('/^\d{2}:\d{2}$/', $end) !== 1) {
            return ['valid' => false, 'error' => 'Heure invalide.'];
        }
        if ($start >= $end) {
            return ['valid' => false, 'error' => 'L\'heure de fin doit être supérieure à l\'heure de début.'];
        }

        return [
            'valid' => true,
            'date' => $date,
            'start' => $start,
            'end' => $end,
            'reservable' => $reservable,
        ];
    }

    private function isDisponibiliteReserved(Disponibilite $slot, RendezVousRepository $rdvRepo): bool
    {
        return $rdvRepo->countActiveForDisponibilite($slot) > 0;
    }
}

