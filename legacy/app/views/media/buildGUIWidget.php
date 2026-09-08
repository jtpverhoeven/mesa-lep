<div class="control-group" id="{widget_title}_widgetCG">
    <label class="control-label">{table_title}</label>

    <div class="controls">
        <table id="{widget_title}TestTable" class="table table-noborder">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Naam</th>
                    <th>Type</th>
                    <th>Verwacht</th>
                    <th>Opties</th>
                </tr>
            </thead>
            <tbody id="{widget_title}Table">
            {existing_rows}
            </tbody>
        </table>

        <select id="{selector_name}" name="{selector_name}" class="widgetSelector input-block-level">
            {available_confirmations}
        </select>

    </div>
</div>
