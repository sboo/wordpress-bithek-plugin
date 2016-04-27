<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">

    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" enctype="multipart/form-data" name="bithek_settings"
          action="options-general.php?page=<?php echo $this->plugin_name; ?>">
        <table class="form-table">
            <?php if (!is_null($this->last_update_datetime)) { ?>
                <tr>
                    <th>
                        <label for="<?php echo $this->plugin_name; ?>-import">
                            <?php esc_attr_e('Letztes update', $this->plugin_name); ?>
                        </label>
                    </th>
                    <td>
                        <?php echo $this->last_update_datetime->format('d.m.Y H:i:s') ?>
                    </td>
                </tr>
            <?php } ?>
            <tr>
                <th>
                    <label for="<?php echo $this->plugin_name; ?>-import">
                        <?php esc_attr_e('Neue Datei importieren', $this->plugin_name); ?>
                    </label>
                </th>
                <td>
                    <input type="file" id="<?php echo $this->plugin_name; ?>-import"
                           name="<?php echo $this->plugin_name; ?>-import"/>
                </td>
            </tr>
        </table>
        <p>
            <strong>Die Verabeitung dauert ca. 5 Minuten. In der Zwischenzeit diese Seite nicht verlassen oder neu
                laden!</strong>
        </p>
        <?php submit_button('Save all changes', 'primary', 'submit', true); ?>
    </form>

</div>