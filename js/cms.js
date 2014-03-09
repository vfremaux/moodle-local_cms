
function set_value(url, cmsbaseurl) {
    var frm = opener.document.forms['cmsEditPage'].elements['url'];

    if ( frm ) {
        frm.value = cmsbaseurl + url;
        frm.select();
        frm.focus();
    }
    window.close();
}
