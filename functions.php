<?php

add_action('wp_enqueue_scripts', function () {
    // Patch de tooltipy afin de ne pas supprimer certains espaces
    // Voir https://wordpress.org/support/topic/whitespace-lost-after-keyword/
    wp_add_inline_script('kttg-tooltips-functions-script', <<<'JS'
(function() {
    if (typeof window.findAndReplaceDOMText !== 'function') return;

    var original = window.findAndReplaceDOMText;

    function patchedFindAndReplaceDOMText(node, options) {
        if (options && typeof options.replace === 'function') {
            var origReplace = options.replace;

            options.replace = function() {
                var portion = arguments[0];
                var result  = origReplace.apply(this, arguments);

                if (
                    result === "" &&
                    (portion.text === "" ||
                     portion.text === " " ||
                     portion.text === "\t" ||
                     portion.text === "\n")
                ) {
                    return portion.text;
                }

                return result;
            };
        }

        return original(node, options);
    }

    // Copier toutes les propriétés statiques
    Object.assign(patchedFindAndReplaceDOMText, original);

    // Installer le wrapper
    window.findAndReplaceDOMText = patchedFindAndReplaceDOMText;
})();
JS
    );
}, 20);
