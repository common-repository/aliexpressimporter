<div class="text_content">
    <h3>Common setting</h3>
    <table class="settings_table">
        <tr valign="top">
            <td scope="row" class="titledesc"><label for="aeidn_ali_per_page">Products per page</label></td>
            <td class="forminp forminp-text">
                <input type="text" id="aeidn_ali_per_page" name="aeidn_ali_per_page"
                       value="<?php echo get_option('aeidn_ali_per_page', 20); ?>"/>
                <span class="description">the maximum number of items is 40</span>
            </td>
        </tr>
        <tr valign="top">
            <td scope="row" class="titledesc"><label for="aeidn_ali_local_currency">Local Currency: </label></td>
            <td class="forminp forminp-select">
                <?php $cur_aeidn_ali_local_currency = get_option('aeidn_ali_local_currency', 'usd'); ?>
                <select name="aeidn_ali_local_currency" id="aeidn_ali_local_currency">
                    <option value=""> -</option>
                    <option value="usd"
                            <?php if ($cur_aeidn_ali_local_currency === 'usd'): ?>selected="selected"<?php endif; ?>>usd
                    </option>
                    <option value="rub"
                            <?php if ($cur_aeidn_ali_local_currency === 'rub'): ?>selected="selected"<?php endif; ?>>rub
                    </option>
                    <option value="gbp"
                            <?php if ($cur_aeidn_ali_local_currency === 'gbp'): ?>selected="selected"<?php endif; ?>>gbp
                    </option>
                    <option value="brl"
                            <?php if ($cur_aeidn_ali_local_currency === 'brl'): ?>selected="selected"<?php endif; ?>>brl
                    </option>
                    <option value="cad"
                            <?php if ($cur_aeidn_ali_local_currency === 'cad'): ?>selected="selected"<?php endif; ?>>cad
                    </option>
                    <option value="aud"
                            <?php if ($cur_aeidn_ali_local_currency === 'aud'): ?>selected="selected"<?php endif; ?>>aud
                    </option>
                    <option value="eur"
                            <?php if ($cur_aeidn_ali_local_currency === 'eur'): ?>selected="selected"<?php endif; ?>>eur
                    </option>
                    <option value="inr"
                            <?php if ($cur_aeidn_ali_local_currency === 'inr'): ?>selected="selected"<?php endif; ?>>inr
                    </option>
                    <option value="uah"
                            <?php if ($cur_aeidn_ali_local_currency === 'uah'): ?>selected="selected"<?php endif; ?>>uah
                    </option>
                    <option value="jpy"
                            <?php if ($cur_aeidn_ali_local_currency === 'jpy'): ?>selected="selected"<?php endif; ?>>jpy
                    </option>
                    <option value="mxn"
                            <?php if ($cur_aeidn_ali_local_currency === 'mxn'): ?>selected="selected"<?php endif; ?>>mxn
                    </option>
                    <option value="idr"
                            <?php if ($cur_aeidn_ali_local_currency === 'idr'): ?>selected="selected"<?php endif; ?>>idr
                    </option>
                    <option value="try"
                            <?php if ($cur_aeidn_ali_local_currency === 'try'): ?>selected="selected"<?php endif; ?>>try
                    </option>
                    <option value="sek"
                            <?php if ($cur_aeidn_ali_local_currency === 'sek'): ?>selected="selected"<?php endif; ?>>sek
                    </option>
                </select>
            </td>
        </tr>
    </table>
    <h3>Import setting</h3>
    <table class="settings_table">
        <tr valign="top">
            <td scope="row" class="titledesc"><label for="aeidn_ali_links_to_affiliate">Convert all links from
                    description to affiliate links</label></td>
            <td class="forminp forminp-text"><input type="checkbox" id="aeidn_ali_links_to_affiliate"
                                                    name="aeidn_ali_links_to_affiliate" value="yes"
                                                    <?php if (get_option('aeidn_ali_links_to_affiliate', false)): ?>checked<?php endif; ?>/>
            </td>
        </tr>
    </table>
</div>