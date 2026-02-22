<?php

namespace App\EventSubscriber;

use App\Repository\RendezVousRepository;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    private $rdvRepository;
    private $router;

    public function __construct(
        RendezVousRepository $rdvRepository,
        UrlGeneratorInterface $router
    ) {
        $this->rdvRepository = $rdvRepository;
        $this->router = $router;
    }

    public static function getSubscribedEvents()
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
        $rdvs = $this->rdvRepository->createQueryBuilder('r')
            ->where('r.date BETWEEN :start AND :end')
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();

        // Transformer chaque RDV en événement pour le calendrier
        foreach ($rdvs as $rdv) {
            // Créer l'événement (titre, date début, date fin)
            $event = new Event(
                $rdv->getNomPatient() . ' (Dr. ' . $rdv->getNomPsychologue() . ')',
                $rdv->getDate(),
                (clone $rdv->getDate())->modify('+1 hour') // Durée par défaut
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