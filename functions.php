<?php

add_action('wp_enqueue_scripts', function () {
    // Patch de tooltipy afin de ne pas supprimer certains espaces
    // Voir https://wordpress.org/support/topic/whitespace-lost-after-keyword/
    wp_add_inline_script('kttg-tooltips-functions-script', <<<'JS'
(function() {
    if (typeof window.findAndReplaceDOMText !== 'function') return;

    var original = window.findAndReplaceDOMText;

    function patchedFindAndReplaceDOMText(node, options) {

        // --- PATCH 1 : élargir la RegExp "find" de Tooltipy pour inclure l’apostrophe typographique ’
        if (options && options.find instanceof RegExp) {
            var src = options.find.source;

            // Si la regex contient déjà ' mais pas encore ’, on l’ajoute dans les classes de caractères
            // Exemple : [...']  ->  [...'\u2019]
            var patchedSrc = src.replace(/¤(?=])/g, "¤\u2019");
            if (patchedSrc !== src) {
                try {
                    options.find = new RegExp(patchedSrc, options.find.flags);
                } catch (e) {
                    // En cas d'erreur de reconstruction, on ne touche à rien
                }
            }
        }

        // --- PATCH 2 : corriger le replace pour ne pas supprimer les portions constituées uniquement de whitespace
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
