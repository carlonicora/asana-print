<h1><?php echo $username; ?>'s Tasks</h1>
<h2>New</h2>
<?php
foreach ($new as $task){
    ?>
    <input type="checkbox"><?php echo $task->name; ?> <?php if ($task->inGlobalList == true) echo '<em>['.$task->workspace.']</em>' ?> <a href="https://app.asana.com/0/<?php echo $task->workspaceId; ?>/<?php echo $task->id; ?>/" target="_blank">&gt;</a> <br/>
    <?php
}
?>
<h2>today</h2>
<?php
foreach ($today as $task){
    ?>
    <input type="checkbox"><?php echo $task->name; ?> <?php if ($task->inGlobalList == true) echo '<em>['.$task->workspace.']</em>' ?> <a href="https://app.asana.com/0/<?php echo $task->workspaceId; ?>/<?php echo $task->id; ?>/" target="_blank">&gt;</a> <br/>
    <?php
}
?>
<h2>upcoming</h2>
<?php
foreach ($upcoming as $task){
    ?>
<input type="checkbox"><?php echo $task->name; ?> <?php if ($task->inGlobalList == true) echo '<em>['.$task->workspace.']</em>' ?> <a href="https://app.asana.com/0/<?php echo $task->workspaceId; ?>/<?php echo $task->id; ?>/" target="_blank">&gt;</a> <br/>
<?php
}
?>