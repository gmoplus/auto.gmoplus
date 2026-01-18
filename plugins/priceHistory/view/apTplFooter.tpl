<script>
    var ph_listing_id = '{$smarty.get.id}';
    $('#edit_listing > fieldset').first().after('<div id="ph-container"></div>');
    $('#ph-container').load(rlPlugins + 'priceHistory/admin/price_history_edit.php?id=' + ph_listing_id);
</script>
