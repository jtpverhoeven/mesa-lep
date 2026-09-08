<table id="packTable" class="table table-condensed table-bordered" style="margin-top: 20px">
    <thead>
        <tr>
            <th width="40px"><i class="icon-check"></i></th>
            <th>ID</th>
            <th>Name</th>
            <th>Tags</th>

        </tr>
    </thead>

    <tbody>

        <tr id="packet_{id}">
            <td><input type="radio" name="selectedPacket" id="selectedPacket" value="{id}" /></td>
            <td>{id}</td>
            <td>{name}</td>
            <td>{tags}</td> 
        </tr>

    </tbody>
</table>