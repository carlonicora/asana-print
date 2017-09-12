Please select the user for whom you want to print the tasks
<ul>
<?php
foreach ($users as $user){
?>
    <li><a href="<?php echo $baseUrl;?><?php echo $workspaceId; ?>/<?php echo $user->id; ?>" target="_blank"><?php echo $user->name; ?></a> <em>(list for all workspaces <a href="<?php echo $baseUrl;?>*/<?php echo $user->id; ?>" target="_blank">&gt;</a>)</em></li>
<?php
}
?>
</ul>