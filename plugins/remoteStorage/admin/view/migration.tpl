<!-- RemoteStorage migration tpl -->

<style>{literal}
.remote-storage-p {
    font-size: 14px;
    line-height: 20px;
}
.remote-storage .red {
    font-size: 14px;
}
.remote-storage .loading-interface {
    border-top: 1px #cccccc solid;
    margin-top: 10px;
    padding-top: 18px;
    display: none;
}
.remote-storage .progress-bar {
    max-width: 600px;
    height: 5px;
    background: #e2e2e2;
    margin: 10px 0;
}
.remote-storage .progress-bar > div {
    height: 100%;
    width: 0;
    background: #748645;
    transition: width 0.2s ease;
}
.remote-storage .progress-error-message {
    margin-top: 15px;
    display: none;
}
.remote-storage .progress-error-message > li:not(:first-child) {
    padding-top: 2px;
}
{/literal}</style>

{if !$rsDownloadBucketID}
    {include file='blocks/m_block_start.tpl'}
{/if}

<div class="remote-storage">
    {if $rsCountNotMigratedMedia}
        {if !$rsDownloadBucketID}
            <p class="remote-storage-p">
                {assign var='replace_var' value=`$smarty.ldelim`count`$smarty.rdelim`}
                {$lang.rs_files_not_migrated|replace:$replace_var:$rsCountNotMigratedMedia}
            </p>
        {/if}

        <div>
            <input id="rs_start_migration" type="button" value="{$lang.rs_start}" />
        </div>
    {else}
        <p class="remote-storage-p">{$lang.rs_all_files_migrated}</p>
    {/if}

    <div class="loading-interface">
        <div class="progress">
            {assign var='replace_files' value=`$smarty.ldelim`files`$smarty.rdelim`}
            {assign var='replace_file' value=`$smarty.ldelim`file`$smarty.rdelim`}
            {$lang.rs_file_upload_info|replace:$replace_files:$rsCountNotMigratedMedia|replace:$replace_file:1}
        </div>
        <div class="progress-bar"><div></div></div>
        <div class="progress-info">
            {assign var='replace_var' value=`$smarty.ldelim`percent`$smarty.rdelim`}
            {$lang.rs_migration_status|replace:$replace_var:'<span>0</span>'}
        </div>
        <ul class="progress-error-message red"></ul>
    </div>
</div>

{if !$rsDownloadBucketID}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
{/if}

<script>{literal}
$(function() {
    let $loadingInterface = $('.remote-storage .loading-interface');
    let $progressBar      = $loadingInterface.find('.progress-bar > div'),
        $errorArea        = $loadingInterface.find('.progress-error-message'),
        $progress         = $loadingInterface.find('.progress'),
        $progressInfo     = $loadingInterface.find('.progress-info > span'),
        currentFile       = 0,
        totalFiles        = {/literal}{$rsCountNotMigratedMedia}{literal},
        // ID of bucket from which need to make the reverse migration (from storage -> to local)
        downloadBucketID  = {/literal}{if $rsDownloadBucketID}{$rsDownloadBucketID}{else}0{/if}{literal},
        inProgress        = false;

    /**
     * Upload file function
     */
    const rsUploadFile = function () {
        inProgress = true;

        flynax.sendAjaxRequest(
            'rsMigrateFile',
            {file: currentFile, total: totalFiles, downloadBucketID: downloadBucketID},
            function(response) {
                if (response.status === 'OK') {
                    if (currentFile < totalFiles) {
                        if ((currentFile + response.limit) > totalFiles) {
                            let rest = totalFiles - currentFile;
                            currentFile += rest;
                        } else {
                            currentFile += response.limit;
                        }

                        let progress = Math.round((currentFile * 100) / totalFiles);
                        progress = progress > 100 ? 100 : progress;
                        $progressBar.width(progress + '%');
                        $progressInfo.text(progress);

                        $progress.text(lang.rs_file_upload_info.replace('{files}', totalFiles).replace('{file}', currentFile));

                        rsUploadFile();
                    } else if (currentFile === totalFiles) {
                        $progressBar.width('100%');
                        $progressInfo.text(100);

                        inProgress = false;

                        if (downloadBucketID) {
                            $('div.modal-window > div > span:last').trigger('click');

                            if (typeof rsDeleteServer === 'function') {
                                rsDeleteServer(downloadBucketID);
                            }
                        } else {
                            printMessage('notice', lang.rs_migration_completed);
                            $progress.text(lang.rs_migration_completed);
                        }
                    }
                } else {
                    rsError(response.message ? response.message : lang.system_error);
                }
            }, function (response) {
                rsError(response.message ? response.message : lang.system_error);
            }
        );

        if (downloadBucketID) {
            // Reassign document click handler to prevent closing popup until migration will be finished
            $(document).off('click touchstart').on('click touchstart', function () {});

            $('div.modal-window > div > span:last').hide();

            $(window).bind('beforeunload', function () {
                if (inProgress) {
                    return 'Uploading the data is in process; closing the page will stop the process.';
                }
            });
        }
    };

    /**
     * Error handler
     * @param data
     */
    const rsError = function(data) {
        $errorArea.append($('<li>').text(data)).show();
        $progressBar.css('width', '0');
        inProgress = false;
    };

    $('#rs_start_migration').click(function() {
        $(this).parent().fadeOut(function() {
            $loadingInterface.fadeIn(function() {
                rsUploadFile();
            });
        });
    });

    $(window).bind('beforeunload', function() {
        if (inProgress) {
            return 'Uploading the data is in process; closing the page will stop the process.';
        }
    });
});
{/literal}</script>

<!-- RemoteStorage migration tpl end -->
