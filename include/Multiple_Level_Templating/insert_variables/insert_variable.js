function formatFieldData(relData) {
    i = 0;
    html = "";


    $.each(relData, function (key, value) {

        if(key)
        {
            var rel_path = getInPathVars();

            html += "<p title='"+ rel_path + "field:" + key + "{/var}' class='draggable'>" + value + "</p>";
            //html += "<p class='draggable'>" + rel_path + "field:" + key + "{/var}</p>";
        }
        i++;
    });

    return html;
}


function formatTreeData(relData) {
    i = 0;
    html = "";
    $(relData).each(function () {

        if (relData[i].type == 'relationship') {
            html += "<li class='expanded'><a href='#' data='" + relData[i].module + "' rel='" + relData[i].rel_name + "'>" + relData[i].label + "</a><ul></ul></li>";
        } else {
            html += "<li class='leaf'><a href='#' class='hasNoChildMenu' data='" + relData[i].module + "' rel='" + relData[i].rel_name + "'>" + relData[i].label + "</a></li>";

        }

        i++;
    });

    return html;

}

function loadTreeLeafData(module) {
    $.ajaxSetup({"async": false});
    var result = $.getJSON('index.php',
        {
            'module': 'AOS_PDF_Templates',
            'action': 'getModuleFields',
            'aos_pdf_templates': module,
            'view': 'JSON'
        }
    );
    $.ajaxSetup({"async": true});

    return formatFieldData(JSON.parse(result.responseText));
}

function loadTreeData(module) {

    $.ajaxSetup({"async": false});
    var result = $.getJSON('index.php',
        {
            'module': 'AOS_PDF_Templates',
            'action': 'getModuleTreeData',
            'aos_pdf_templates': module,
            'view': 'JSON'
        }
    );
    $.ajaxSetup({"async": true});

    return formatTreeData(JSON.parse(result.responseText));
}


function processTreeData() {
    $("ul#insert_fields_list").columnview(
        {
            getSubtree: function (elt, isRoot) {

                var module = $("#template_module").val();

                if (elt && isRoot == false) {

                    if ($(elt).hasClass("hasChildMenu")) {


                        html = loadTreeData($(elt).attr('data'));

                    }

                } else {

                    html = loadTreeData(module);

                }
                return html;
            }
        });
}


function displayFields($self, container) {

    var previewcontainer = $('.feature');

    if (previewcontainer.length == 0) {
        $(".previewcontainer").length;

        var previewcontainer = $('<div/>').addClass('feature').appendTo(container);
        html = loadTreeLeafData($self.attr('data'));
        $(previewcontainer).html(html);

        var remainingspace = 0;
        $.each($(container).children('div').slice(0, -1), function (i, item) {
            remainingspace += $(item).width();
        });
        var fillwidth = $(container).width() - remainingspace;
        $(previewcontainer).css({'top': 0, 'left': remainingspace}).width(fillwidth).show();

        setUpUI();
    }

}

function getInPathVars() {

    var modules = [];
    var return_string = '{var}';

    $('.inpath').not('.hasNoChildMenu').each(function () {
        modules.push($(this).attr('rel'));
    });

    $(modules).each(function () {

        return_string += 'rel:' + this + ','

    });

    return return_string;
}

function setUpUI(){
        $('.draggable').draggable({
            appendTo: 'body',
            scroll: false,
            helper: 'clone',
            cursor: "pointer",
            iframeFix: true,
            start: function () {
                contents = $(this).attr('title');
            }
        });
        $('#description_ifr').droppable({
            drop: function (event, ui) {
                tinymce.get("description").execCommand('mceInsertContent',false,contents);
            }
        });
        $('#pdfheader_parent').droppable({
            drop: function (event, ui) {
                tinymce.get("pdfheader").execCommand('mceInsertContent',false,contents);
            }
        });
        $('#pdffooter_parent').droppable({
            drop: function (event, ui) {
                tinymce.get("pdffooter").execCommand('mceInsertContent',false,contents);
            }
        });
}


$(document).ready(function () {

    var load_insert_fields = setInterval(function(){
        if ($("#insert_fields_list").length > 0){ // Check if element has been found
            processTreeData();
        }
        clearInterval(load_insert_fields);
    },1);


    $('#template_module').change(function () {
        $("#insert_fields_container").html("<ul id='insert_fields_list'></ul>");
        processTreeData();
    });

    var contents = '';
    $('.draggable').draggable({
        containment: "mceIframeContainer mceFirst mceLast",
        revert: true,
        start: function () {
            contents = $(this).attr('title');
        }
    });



});