<table class="table table-condensed " id="{table_id}">
    
    <thead>
        <tr>
            <th>{MESA_LOG_DATE}</th>
            <th>{MESA_LOG_TIME}</th>
            <th>{MESA_LOG_IP}</th>
            <th>{MESA_LOG_USER}</th>
            <th>{MESA_LOG_LEVEL}</th>
            <th>{MESA_LOG_MESSAGE}</th>
        </tr>
    </thead>
    
    <tbody>
        
        <tr style="background-color: {color}">
            
            <td>{date}</td>
            <td>{time}</td>
            <td>{ip}</td>
            <td><a href="{LB}/profiles/view/{user_name}">{user_name}</a></td>
            <td>{level}</td>
            <td>{message}</td>
            
        </tr>
        
    </tbody>
    
</table>