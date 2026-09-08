<table class="table table-condensed table-bordered" style="margin-top: 20px" id="calctable">
    <thead>
        <tr>
            <th><i class="icon-check"></i></th>
            <th>Bestandsnaam</th>
            <th>In gebruik</th>
        </tr>
    </thead>

    <tbody>

        <tr id="tr_{filename}">
            <td><input type="radio" name="selectedCalc" id="selectedCalc" value="{filename}" /></td>
            <td>{name}</td>
            <td><a href="#" onClick="showCalcUsage('{filename}');">{in_use}</a></td>
        </tr>

    </tbody>
</table>
