<?php

namespace App\Support;

class PermissionCatalog
{
    /** @return array<string, array<string, string>> */
    public static function groups(): array
    {
        return [
            'Gebruikersbeheer' => [
                'administration.access' => 'Administratiepaneel openen',
                'users.manage' => 'Gebruikers beheren',
                'groups.manage' => 'Gebruikersgroepen beheren',
            ],
            'Werkstroomconfiguratie' => [
                'research-profiles.manage' => 'Onderzoeksprofielen aanpassen',
                'assays.manage' => 'Analyses beheren',
                'assay-types.manage' => 'Analysetypen beheren',
                'sampling-procedures.manage' => 'Monsternameprocedures aanpassen',
            ],
            'Basisconfiguratie' => [
                'project-fields.manage' => 'Projectvelden definieren',
                'assay-fields.manage' => 'Globale analysevelden definieren',
                'sample-fields.manage' => 'Monstervelden definieren',
                'sampling-procedure-fields.manage' => 'Monsternameprocedurevelden definieren',
                'media.manage' => 'Media, bevestigingen en materiaal aanpassen',
                'settings.advanced' => 'Geavanceerde instellingen',
            ],
            'Laboratoriumwerk' => [
                'samples.create' => 'Monsters aanmelden',
                'samples.assign-research' => 'Onderzoek aan monsters verbinden',
                'samples.view' => 'Monster opzoeken',
                'samples.update' => 'Monsterdetails aanpassen',
                'samples.update-research' => 'Retrospectief onderzoek aan monsters wijzigen',
                'samples.create-legionella' => 'Legionellamonsters aanmelden',
                'samples.create-rodac' => 'Rodac-monsters aanmelden',
                'samples.add-assays-empty' => 'Analyses aan lege monsters toevoegen',
                'samples.list' => 'Monsterlijst openen',
                'dilutions.update' => 'Verdunningen aanpassen',
                'samples.notes.update' => 'Monsternotities aanpassen',
                'samples.client-description.update' => 'Klantomschrijving van monsters aanpassen',
                'samples.photos.manage' => 'Monsterfoto\'s bewerken',
            ],
            'Projecten en resultaten' => [
                'projects.view' => 'Projecten zoeken',
                'projects.update' => 'Projectdetails aanpassen',
                'projects.authorisation.manage' => 'Projectautorisatie aanpassen',
                'projects.samples.remove' => 'Monsters uit projecten verwijderen',
                'projects.notes.create' => 'Opmerkingen aan projecten toevoegen',
                'results.veto' => 'Vetresultaten instellen',
                'projects.delete' => 'Projecten verwijderen',
                'projects.authorisation.block' => 'Projectautorisatie blokkeren',
                'confirmations.reset' => 'Bevestigingen resetten',
            ],
            'Klanten' => [
                'clients.view' => 'Klantinformatie inzien',
                'clients.manage' => 'Klanten aanmaken, bewerken en verwijderen',
                'client-sync.access' => 'Klantsynchronisatiepaneel openen',
                'clients.import' => 'Klantimport openen',
                'clients.export' => 'Klantenlijst exporteren',
                'clients.categories.update' => 'Klantcategorie-associaties aanpassen',
                'portal.access' => 'Voorportaal openen',
            ],
            'Rapportage en hulpmiddelen' => [
                'labels.design' => 'Labelontwerppagina openen',
                'labels.print-settings' => 'Labelprintconfiguratie aanpassen',
                'logbooks.view' => 'Logboeken inzien',
                'scan-to-print.use' => 'Scan-to-print gebruiken',
                'assurance-form.view' => 'Borgingsformulier openen',
                'lists.view' => 'Lijstenpagina openen',
                'reports.parameters.update' => 'Parameters op rapportage bewerken',
                'worksheets.manage' => 'Werklijsten beheren',
                'assays.search-add' => 'Algemene analysezoekfunctie gebruiken',
                'shelf-life-studies.view' => 'THT-onderzoekslijst openen',
            ],
            'Revisies en datamining' => [
                'revisions.samples.view' => 'Monsterrevisies inzien',
                'revisions.projects.view' => 'Projectrevisies inzien',
                'revisions.results.view' => 'Resultaatrevisies inzien',
                'data-mining.use' => 'Dataminingfuncties gebruiken',
                'data-mining.deauthorisation-reasons' => 'Deautorisatieredenen via datamining opvragen',
            ],
        ];
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        return array_merge(...array_values(self::groups()));
    }

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::all());
    }
}