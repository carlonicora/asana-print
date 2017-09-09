<div id="workspaces">
Select your workspace:
<ul>
<?php
foreach ($workspaces as $workspace){
    ?>
    <li data-workspaceId="<?php echo $workspace->id; ?>"><a href="#" class="selectworkspace"><?php echo $workspace->name; ?></a></li>
    <?php
}
?>
</ul>
</div>
<div id="users">
</div>
<script>
    $('.selectworkspace').click(function() {
        var workspaceId = $(this).closest('li').attr('data-workspaceId');

        $.get("<?php echo $baseUrl;?>" + workspaceId, function(result){
            $("#users").html(result);
        });
    });
</script>