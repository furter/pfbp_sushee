/* to open links in new tabs, but not the internal links
     */
var isExternal = function(href) {
    /*
    "-1" = rien trouvé
    "127.0.0.1" = localhost
    "===" = est
    "!==" = n'est pas (donc "!== -1" = n'a pas rien trouvé ;)
    dans la console: document.location.host [enter] -> trouve quel url c'est
    dans indexOf(document.location.host), document.location.host c'est le host/url du site (ou localhost)
     * isExternal("http://autre-site.com/")
     * true
     * isExternal("/path/")
     * false
     * isExternal("http://spion.me/publications/")
     * false
     * isExternal("http://localhost:8000/publications/")
     * false
     * isExternal("http://127.0.0.1:8000/publications/")
     * false
     */
    if (href.indexOf("http") === -1 || href.indexOf(document.location.host) !== -1 || href.indexOf("localhost") !== -1 || href.indexOf("127.0.0.1") !== -1 ) {
        return false;
    }
    return true;
};


$(document).ready(function() {
                        // action: si c'est externe, on attribue à "ça" une target pour le lien qui est un nouvel onglet
             $("a[href]").each(function() {
                     if (isExternal($(this).attr('href')) ) {
                        $(this).attr('target', '_blank')
                     }
                 });
             $("#changeimage").click(function () {
                 $("#toggle1").toggle();
                 $("#toggle2").toggle();
             });
             $("#changeimage2").click(function () {
                 $("#toggle3").toggle();
                 $("#toggle4").toggle();
             });
             $("#changeimage3").click(function () {
                 $("#toggle5").toggle();
                 $("#toggle6").toggle();
             });
             $("#changeimage4").click(function () {
                 $("#toggle10").toggle();
                 $("#toggle11").toggle();
             });
             
});
