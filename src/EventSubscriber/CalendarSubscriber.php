<?php

namespace App\EventSubscriber;

use App\Entity\RendezVous;
use App\Repository\RendezVousRepository;
use Doctrine\DBAL\Types\Types;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RendezVousRepository $rdvRepository,
        private UrlGeneratorInterface $router
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar)
    {
        // Récupérer la période affichée par le calendrier
        $start = $calendar->getStart();
        $end = $calendar->getEnd();
        
        // Requête pour récupérer les RDV de la période
        $startDate = $start instanceof \DateTimeInterface ? \DateTimeImmutable::createFromMutable(\DateTime::createFromInterface($start))->setTime(0, 0, 0) : null;
        $endDate = $end instanceof \DateTimeInterface ? \DateTimeImmutable::createFromMutable(\DateTime::createFromInterface($end))->setTime(0, 0, 0) : null;

        /** @var list<RendezVous> $rdvs */
        $rdvs = ($startDate && $endDate)
            ? $this->rdvRepository->createQueryBuilder('r')
                ->join('r.disponibilite', 'd')
                ->addSelect('d')
                ->andWhere('d.date BETWEEN :start AND :end')
                ->andWhere('r.statut IN (:st)')
                ->setParameter('start', $startDate, Types::DATE_MUTABLE)
                ->setParameter('end', $endDate, Types::DATE_MUTABLE)
                ->setParameter('st', [RendezVous::STATUT_EN_ATTENTE, RendezVous::STATUT_ACCEPTE])
                ->getQuery()
                ->getResult()
            : [];

        // Transformer chaque RDV en événement pour le calendrier
        foreach ($rdvs as $rdv) {
            $date = $rdv->getDate();
            if ($date === null) {
                continue;
            }
            // Créer l'événement (titre, date début, date fin)
            $event = new Event(
                $rdv->getNomPatient() . ' (Dr. ' . $rdv->getNomPsychologue() . ')',
                $date,
                (clone $date)->modify('+1 hour') // Durée par défaut
            );

            // ✅ Vérifier que l'ID existe avant de générer l'URL
            $url = null;
            if ($rdv->getId()) {
                try {
                    $url = $this->router->generate('app_rendez_vous_show', [
                        'id' => $rdv->getId()
                    ]);
                } catch (\Exception $e) {
                    // Ignorer si la route n'existe pas
                    $url = null;
                }
            }

            // Ajouter des options (couleur, URL, etc.)
            $event->setOptions([
                'backgroundColor' => $rdv->getType() && $rdv->getType()->getCouleur() 
                    ? $rdv->getType()->getCouleur() 
                    : '#3788d8',
                'borderColor' => $rdv->getType() && $rdv->getType()->getCouleur() 
                    ? $rdv->getType()->getCouleur() 
                    : '#3788d8',
                'textColor' => '#ffffff',
                'url' => $url, // ✅ URL sécurisée (peut être null)
            ]);

            // Ajouter l'événement au calendrier
            $calendar->addEvent($event);
        }
    }
}
