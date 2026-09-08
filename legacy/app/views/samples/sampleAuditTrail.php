<h3>Audit Trail voor monster: {barcode} <span class="pull-right"><a href='{LB}/samples/sampleAuditTrail/{barcode}' class="btn btn-mini"><i class='icon-print'></i> Afdruk weergave</a></span></h3>

<div class="alert alert-block {borgMsg}">Dit monster is nog niet ingezet, enkel algemene momenteel  beschikbaar</div>

<table class="table table-condensed table-bordered">
  <thead><th colspan="2">Opdrachtgever</th></thead>
  <tbody>
    <tr><td style="width: 35%;">Opdrachtgever</td> <td><a href="{LB}/clients/show/{client_id}">{opdrachtgever}</a></td></tr>
    <tr><td>Contactpersoon</td> <td>{contactpersoon}</td></tr>
    <tr><td>Adresgegevens</td> <td>{street} {number}</td></tr>
    <tr><td>Plaats</td> <td>{place}</td></tr>
  </tbody>
</table>

<table class="table table-condensed table-bordered">
  <thead><th colspan="2">Project gegevens</th></thead>
  <tbody>
    <tr><td style="width: 35%;">Onderdeel van projectnummer</td> <td>{projectid}</td></tr>
    <tr><td style="width: 35%;">Klant-referentie</td> <td>{projectName}</td></tr>
    <tr><td>Aantal monsters in project</td> <td>{numberofsamples}</td></tr>
    <tr><td>Aantal revisies van project</td> <td>{revision}</td></tr>
    <tr><td>Datum laatste versie geautoriseerd</td> <td>{last_auth}</td></tr>
    <tr><td>Geautoriseerd door</td> <td>{last_auth_by}</td></tr>

    <tr><td>Laatste analyse certificaat gegenereerd op</td> <td>{last_rap}</td></tr>
    <tr><td>Laatste analyse certificaat gegenereerd door:</td> <td>{last_rap_by}</td></tr>
    <tr><td>Project opmerkingen</td> <td>{project_notes}</td></tr>


  </tbody>
</table>

<table class="table table-condensed table-bordered">
  <thead><th colspan="2">Algemene monster gegevens:</th></thead>
  <tbody>
    <tr><td style="width: 35%;">Datum bemonstering</td> <td>{date_sampling}</td></tr>
    <tr><td>Datum en tijd ontvangst lab</td> <td>{date_receive} {time_receive}</td></tr>
    <tr><td>Datum en tijd aanvang analyses</td> <td>{timedate_innoc}</td></tr>

    <tr><td>Monster genomen door</td> <td>{sampling_by}</td></tr>
    <tr><td>Monster methode</td> <td>{sampling_method}</td></tr>

    <tr><td>Monster omschrijving</td> <td>{sample_description}</td></tr>
    <tr><td>Monster details</td> <td>{sample_details}</td></tr>
    <tr><td>Monster notities</td> <td>{sample_notes}</td></tr>

    <tr><td>Opgeslagen in bak in vriezer</td> <td>{stored_in}</td></tr>

    <tr><td>Afgewogen en verdund op  afweegstation:</td> <td>{diluted_at}</td></tr>

    <tr><td>THT-code (indien van toepassing)</td> <td>{tht_code}</td></tr>

  </tbody>
</table>

<span id="noBorgHider" class="{borgHider}">

<table class="table table-condensed table-bordered">
  <thead><th colspan="2">Algemene analyse gegevens</th></thead>
  <tbody>
    <!-- <tr><td style="width: 35%;">Monster afgewogen door</td> <td>{bf_afweeg}</td></tr> -->
    <tr><td style="width: 35%;">Monster ingezet door</td> <td>{bf_inzet}</td></tr>
    <tr><td>Platen gegoten door</td> <td>{bf_gegoten}</td></tr>
  </tbody>
</table>

<table class="table table-condensed table-bordered">
  <thead>
      <tr>
        <th colspan="9">Datums voor onderzochte parameters voor dit monster </th>
      </tr>
      <tr>
        <th>Volgnr.</th>
        <th>Analyse</th>
        <th>Datum in broedstoof</th>
        <th>Tijd in broedstoof</th>
        <th>Datum uit broedstoof</th>
        <th>Tijd uit broedstoof</th>
        <th>Datum aflezen</th>
        <th>Tijd aflezen</th>
        <th>Afgelezen door</th>

      </tr>
  </thead>
  <tbody>
    {analysis_rows}
  </tbody>
</table>

<table class="table table-condensed table-bordered">
  <thead>
      <tr>
        <th colspan="9">Onderzochte parameters voor dit monsters</th>
      </tr>
      <tr>
        <th>Volgnr</th>
        <th>Analyse</th>
        <th>Q</th>
        <th>Techniek</th>
        <th>Intern ref. nummer</th>
        <th>Conformiteit</th>
        <th>Referentie methode</th>
        <th>Analyse ID</th>
        <th>Revisie</th>
      </tr>
    </thead>
    <tbody>
      {assay_rows}
    </tbody>
  </table>



 <table class="table table-condensed table-bordered">
   <thead>
       <tr>
         <th colspan="5">ophoping- en verdunningsvloeistoffen</th>
       </tr>
       <tr>
         <th>Media</th>
         <th>THT-datum</th>
       </tr>
     </thead>
     <tbody>
       {ophopings_table}
     </tbody>
   </table>


  <table class="table table-condensed table-bordered">
    <thead>
        <tr>
          <th colspan="5">Benodigde media voor analyses</th>
        </tr>
        <tr>
          <th>Volgnr</th>
          <th>Media</th>
          <th>THT-datum</th>
          <th>Supplement</th>
          <th>THT-datum Spplmnt.</th>
        </tr>
      </thead>
      <tbody>
        {media_rows}
      </tbody>
    </table>


    <table class="table table-condensed table-bordered">
      <thead>
          <tr>
            <th colspan="3">Benodigd materiaal voor deze analyses</th>
          </tr>
          <tr>
            <th>Volgnr</th>
            <th>Materiaal</th>
            <th>Controle</th>
          </tr>
      </thead>
      <tbody>
        {material_rows}
      </tbody>
    </table>

    <table class="table table-condensed table-bordered">
      <thead>
          <tr>
            <th colspan="7">Uitgevoerde bevestigingen voor deze analyses</th>
          </tr>
          <tr>
            <th>Volgnr</th>
            <th>Analyse</th>
            <th>Bevestiging uitgevoerd</th>
          </tr>
      </thead>
      <tbody>
        {confirmation_rows}
      </tbody>

    </table>


    <table class="table table-condensed table-bordered">
      <thead>
          <tr>
            <th colspan="9">Benodigde media voor deze bevestigingen</th>
          </tr>
          <tr>
            <th>Volgnr</th>
            <th>Verd.</th>
            <th>Duplo</th>
            <th>Naam</th>
            <th>THT</th>
            <th>Ingezet</th>
            <th>Door</th>
            <th>Afgelezen</th>
            <th>Door</th>
          </tr>
      </thead>
      <tbody>
        {confirmation_media}
      </tbody>
    </table>

    <table class="table table-condensed table-bordered">
      <thead>
          <tr>
            <th colspan="2">Eventueel gemaakte opmerkingen bij bevestigingen</th>
          </tr>
          <tr>
            <th>Volgnr.</th>
            <th>Notities</th>
          </tr>
      </thead>
      <tbody>
      {confirmation_notes}
      </tbody>
    </table>



    <table class="table table-condensed table-bordered">
      <thead>
          <tr>
            <th colspan="14">Resultaten van uitgevoerde telling per analyse inclusief eindresultaat</th>
          </tr>
          <tr>
            <th></th>
            <th>telling</th>
            <th>telling</th>
            <th>telling</th>
            <th>telling</th>
            <th>telling</th>
            <th>telling</th>
            <th>telling</th>
            <th>telling</th>
            <th>telling</th>
            <th>telling</th>
            <th>telling</th>
            <th rowspan="2">eind resultaat</th>
            <th rowspan="2">ct-waarde</th>

          </tr>
          <tr>
            <th>Volgnr.</th>
            <th>0</th>
            <th>-1</th>
            <th>-2</th>
            <th>-3</th>
            <th>-4</th>
            <th>-5</th>
            <th>-6</th>
            <th>-7</th>
            <th>-8</th>
            <th>-9</th>
            <th>-10</th>
          </tr>

      </thead>
      <tbody>
        {results_table}
      </tbody>
    </table>



    <table class="table table-condensed table-bordered">
      <thead>
          <tr>
            <th colspan="12">Resultaten uitgevoerde bevestigingstesten per analyse</th>
          </tr>
          <tr>
            <th>Volgnummer</th>
            <th>Verd.</th>
            <th>Duplo.</th>
            <th>Bevestigingstest</th>
            <th>KVE 1</th>
            <th>KVE 2</th>
            <th>KVE 3</th>
            <th>KVE 4</th>
            <th>KVE 5</th>
            <th>Positieve controle</th>
            <th>Negatieve controle</th>
            <th>Blanco</th>
          </tr>

        </thead>
        <tbody>
            {conf_result_table}
        </tbody>
      </table>




    <h6> Analyse revisies revisies </h6>

        <table class="table table-condensed table-bordered">
          <thead>
              <tr>
                <th>Volgnr.</th>
                <th>Revisies</th>
              </tr>
          </thead>
          <tbody>
          {assay_revisions}
          </tbody>
        </table>

    <h6> Project revisies </h6>
    {project_revisions}

    <h6> Monster revisies </h6>
    {sample_revisions}



    <h6> Borgingsformulier revisies </h6>
    {borg_revisions}



</span>
