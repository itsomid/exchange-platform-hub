import Tinymce from 'tinymce';
import 'tinymce/tinymce';
import 'tinymce/skins/ui/oxide/skin.min.css';
import 'tinymce/icons/default/icons.min.js';
import 'tinymce/themes/silver/theme.min.js';
import 'tinymce/models/dom/model.min.js';
import 'tinymce/plugins/directionality/plugin.min.js';
import 'tinymce/plugins/emoticons/plugin.min.js';
import 'tinymce/plugins/emoticons/js/emojis.min.js';
import 'tinymce/plugins/table/plugin.min.js';


try {
    window.Tinymce = Tinymce;
} catch (e) {}

export { Tinymce };
