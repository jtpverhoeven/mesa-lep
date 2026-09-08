<table class="table table-condensed" width="100%" id='exportTable'>
    <thead>
      <tr>
        <th>ID</th>
        <th>Transactie</th>
        <th>Datum</th>
        <th>Type</th>
        <th>Revisie</th>
        <th>Print versie</th>
        <th>Acties</th>
      </tr>
    </thead>

    <tbody>
       <tr>
          <td>{id}</td>
          <td >{transaction}</td>
          <td>{date}</td>
          <td>{type}</td>
          <td>{revision}</td>
          <td>{print_version}</td>
          <td>
            <a href="#" onClick="downloadRevision('{id}');" class="btn btn-small btn-default"><i class="icon icon-download"></i></a>
            <a href="{LB}/exports/extern/{exportable}"  class="{can_be_exported} btn btn-small btn-default"><i class="icon icon-envelope"></i></a>
          </td>
      </tr>
    </tbody>
  </table>
