<h1><?php echo $username; ?>'s Tasks</h1>
<h2>New</h2>
<?php
foreach ($new as $task){
    ?>
    <input type="checkbox"><?php echo $task->name; ?> <br/>
    <?php
}
?>
<h2>today</h2>
<?php
foreach ($today as $task){
    ?>
    <input type="checkbox"><?php echo $task->name; ?> <br/>
    <?php
}
?>
<h2>upcoming</h2>
<?php
foreach ($upcoming as $task){
    ?>
<input type="checkbox"><?php echo $task->name; ?> <br/>
<?php
}
?>