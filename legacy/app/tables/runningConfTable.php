<table class="table table-condensed" width="100%" id='runningConfTable'>
    <thead>
      <tr>
        <th>Barcode</th>
        <th>Omschrijving</th>
        <th>Klant</th>
        <th>Analyse</th>
        <th>Acties</th>
      </tr>
    </thead>

    <tbody>
       <tr>
          <td>{barcode}</strong></td>
          <td>{description}</td>
          <td><a href="{LB}/clients/show/{clientId}">{client}</a></td>
          <td>{assay_name}</td>
          <td>
            <a href="{LB}/samples/lookup/{barcode_specific}"  class="btn btn-small btn-default"><i class="icon icon-beaker"></i> Open Monster</a>
            <a href="{LB}/projects/search/{project}" class="btn btn-small btn-default"><i class="icon icon-suitcase"></i> Open Project</a>
          </td>
      </tr>
    </tbody>
  </table>
