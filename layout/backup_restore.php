<div>
    <div class="wrap light-tabs" default-rel="backup_settings">
        <h2 class="nav-tab-wrapper">
            <a href="#" class="nav-tab nav-tab-active" rel="backup_settings">Backup settings</a>
            <a href="#" class="nav-tab" rel="restore_settings">Restore settings</a>
            <a href="#" class="nav-tab" rel="backup_products">Backup products</a>
            <a href="#" class="nav-tab" rel="restore_products">Restore products</a>
        </h2>
        <div class="tab_content" rel="backup_settings">
            <div class="separator"></div>
            <div class="text_content">
                <table class="settings_table">
                    <tr>
                        <td><label>Filename</label></td>
                        <td><input id="settings_filename"></td>
                        <td><a class="btn" href="javascript:downloadSettings()">Download</a></td>
                    </tr>
                </table>
            </div>
            <div class="separator"></div>
        </div>
        <div class="tab_content" rel="restore_settings" style="display: none">
            <div class="separator"></div>
            <div class="text_content">
                <form method="POST" id="restore_settings_form" enctype="multipart/form-data">
                    <table class="settings_table">
                        <tr>
                            <td><label>File</label></td>
                            <td><input name="file" type="file"></td>
                            <td><a href="javascript:document.getElementById('restore_settings_form').submit()" class="btn">Restore</a></td>
                        </tr>
                    </table>
                </form>
            </div>
            <div class="separator"></div>
        </div>
        <div class="tab_content" rel="backup_products" style="display: none">
            <div class="separator"></div>
            <div class="text_content">
                <table class="settings_table">
                    <tr>
                        <td><label>Filename</label></td>
                        <td><input></td>
                        <td><a class="btn">Download</a></td>
                    </tr>
                </table>
            </div>
            <div class="separator"></div>
        </div>
        <div class="tab_content" rel="restore_products" style="display: none">
            <div class="separator"></div>
            <div class="text_content">
                <table class="settings_table">
                    <tr>
                        <td><label>File</label></td>
                        <td><input type="file"></td>
                        <td><a class="btn">Restore</a></td>
                    </tr>
                </table>
            </div>
            <div class="separator"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function downloadSettings() {
        location.href = 'admin-ajax.php?action=aeidn_export_settings&filename='+jQuery('#settings_filename').val();
    }
</script> 