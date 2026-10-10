<?php

namespace App\ChangeTracking;

class ChangeType
{
    public const LABELS = [
        '1' => 'Resultaat gewijzigd', '2' => 'Monster aangemaakt', '3' => 'Project aangemaakt',
        '4' => 'Monster ingezet', '5' => 'Monstergegevens gewijzigd', '6' => 'Onderzoek toegevoegd',
        '7' => 'Onderzoek verwijderd', '8' => 'Bevestiging aangevraagd', '9' => 'Projectautorisatie',
        '10' => 'Rapport / resultaat geëxporteerd', '11' => 'Monster / veto gewijzigd', '12' => 'Veto gewijzigd',
        '13' => 'Borgingsformulier gewijzigd', '14' => 'Bevestigingsresultaat gewijzigd',
        '15' => 'Bevestigingsmetadata gewijzigd', '16' => 'Bevestigingswaarde opgeslagen',
        '17' => 'Projectgegevens gewijzigd', '18' => 'Metadata toegevoegd', '19' => 'Metadata verwijderd',
        '20' => 'Projectnotitie gewijzigd', '21' => 'Project naar portal geëxporteerd',
        '22' => 'Project vergrendeld', '23' => 'Project ontgrendeld', '24' => 'Projectklant gewijzigd',
        '25' => 'Bestand toegevoegd', '26' => 'Bestand verwijderd', '27' => 'Bestandszichtbaarheid gewijzigd',
    ];

    public static function label(string $type): string
    {
        return self::LABELS[$type] ?? 'Type '.$type;
    }
}
