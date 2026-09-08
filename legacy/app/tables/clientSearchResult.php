  <table class="table  table-bordered" id="{table_id}">
            <thead>
                <tr>
                    <th> {MESA_CLI_SEARCHID} </th>
                    <th> {MESA_CLI_SEARCHNAME}</th>
                    <th> {MESA_CLI_SEARCHADRES}</th>
                    <th> {MESA_CLI_SEARCHCONTACT}</th>                    
                </tr>
            </thead>
            
            <tbody>                
                <tr {style}>                    
                    <td><a href="{LB}/clients/show/{id}">{id}</a></td>
                    <td><a href="{LB}/clients/show/{id}">{name}</a> <br /><strong>{nonactive}</strong></td>
                    <td>{street_name} {street_number} <br/> {postal_code} {place} <br /> {country} </td>
                    <td>{title} {fname} {lname} <br /> {email} <br /> {telephone} - {cellphone} </td>
                </tr>                
            </tbody>
        </table>