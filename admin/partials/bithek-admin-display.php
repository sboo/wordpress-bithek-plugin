<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">

    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" enctype="multipart/form-data" name="bithek_settings" action="options-general.php?page=<?php echo $this->plugin_name; ?>">
        <table class="form-table">
            <tr>
                <th>
                    <label for="<?php echo $this->plugin_name; ?>-import">
                        <?php esc_attr_e('Neue Datei importieren', $this->plugin_name); ?>
                    </label>
                </th>
                <td>
                    <input type="file" id="<?php echo $this->plugin_name; ?>-import" name="<?php echo $this->plugin_name; ?>-import" />
                </td>
            </tr>
        </table>
        <p>
            <strong>Die Verabeitung dauert ca. 5 bis 10 Minuten. In der Zwischenzeit diese Seite nicht verlassen oder neu laden!</strong>
        </p>
        <?php submit_button('Save all changes', 'primary','submit', TRUE); ?>
    </form>

</div>