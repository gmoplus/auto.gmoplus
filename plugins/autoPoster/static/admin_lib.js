
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : ADMIN_LIB.JS
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

var autoPosterModulesGridClass = function () {

    /**
     * Instance of the autoPosterModulesGridClass
     * @type {autoPosterModulesGridClass}
     */
    var self = this;

    /**
     * Init function
     */
    this.init = function () {
        itemsGrid = new gridObj({
            key: 'autoposter_grid',
            id: 'grid',
            ajaxUrl: rlPlugins + 'autoPoster/admin/auto_poster.inc.php?q=ext',
            defaultSortField: 'name',
            remoteSortable: true,
            title: lang['ap_modules'],
            fields: [
                {name: 'id', mapping: 'ID', type: 'int'},
                {name: 'key', mapping: 'Key', type: 'string'},
                {name: 'Status', mapping: 'Status'},
                {name: 'name', mapping: 'name'}
            ],
            columns: [
                {
                    header: lang['id'],
                    dataIndex: 'id',
                    id: 'ap_id',
                    width: 40,
                    fixed: true
                },{
                    header: lang['name'],
                    dataIndex: 'name',
                    id: 'rlExt_item_bold'
                },{
                    header: lang['ext_status'],
                    dataIndex: 'Status',
                    width: 100,
                    fixed: true,
                    editor: new Ext.form.ComboBox({
                        store: [
                            ['active', lang['ext_active']],
                            ['approval', lang['ext_approval']]
                        ],
                        displayField: 'value',
                        valueField: 'key',
                        typeAhead: true,
                        mode: 'local',
                        triggerAction: 'all',
                        selectOnFocus: true
                    })
                }, {
                    header: lang['ext_actions'],
                    width: 70,
                    fixed: true,
                    dataIndex: 'Key',
                    sortable: false,
                    renderer: function (val, obj, row) {
                        var id = row.data.id;
                        var key = row.data.key;
                        var out = "";

                        // edit
                        out += "<a href=\"" + rlUrlHome + "index.php?controller=auto_poster&action=edit&module=" + key + "\">";
                        out += "<img class='edit' ext:qtip='" + lang['ext_edit'] + "' src='" + rlUrlHome + "img/blank.gif' /></a>"

                        return out;
                    }
                }
            ]
        });
        itemsGrid.init();
        grid.push(itemsGrid.grid);
    };
};

/**
 * @since 1.1.0
 * Autoposter Admin Panel side class
 */
var autoPosterAdminClass = function () {

    /**
     *
     * @type {autoPosterAdminClass}
     */
    var self = this;

    /**
     * Copy provided text to the clipboard
     * @param {string}    text
     * @returns {boolean}       Does text copied succesfull
     */
    this.copyTextToClipBoard = function (text) {
        if (window.clipboardData && window.clipboardData.setData) {
            // IE specific code path to prevent textarea being shown while dialog is visible.
            return clipboardData.setData("Text", text);

        } else if (document.queryCommandSupported && document.queryCommandSupported("copy")) {
            var textarea = document.createElement("textarea");
            textarea.textContent = text;
            textarea.style.position = "fixed";
            document.body.appendChild(textarea);
            textarea.select();
            try {
                return document.execCommand("copy");
            } catch (ex) {
                return false;
            } finally {
                document.body.removeChild(textarea);
            }
        }
    };
};
