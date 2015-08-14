$(document).ready(function(){

    var container = $('body');

    $('#loading').append('<img src="themes/' + theme_name + '/images/img_loading.gif"/>');

    $(container).data("offset",0);

    $(window).scroll(function() {
        if($(window).scrollTop() + window.innerHeight == $(document).height()) {

            var offset = $(container).data("offset");

            $(container).data("offset",scroll_data_loader.load_summary_data(offset));


        }
    });


    $(document).ajaxStart(function(){
        $('#loading').show();
    });
    $(document).ajaxComplete(function(){
        $('#loading').hide();
    });
});

scroll_data_loader = {

    load_summary_data : function(offset){

        offset = offset + 80;

        $.ajax({
            method: "POST",
            url: "index.php?module=Activities&action=loadSummaryData",
            data: {'offset': offset, to_pdf: true,'module' : 'Activities', 'action' : 'loadSummaryData'},
            dataType:'json'
        }) .always(function( result ) {

            jQuery.each(result, function(key, value) {
                scroll_data_loader.append_data(value);

            });
        });

        return offset;
    },

    append_data : function(row_data){


        var html =  '<tr height="20" class="oddListRowS1">';

        if(row_data['MODULE'] != '') {
            html += '<td nowrap="nowrap" valign="top">'+ row_data['IMAGE'] +'</td>';
        }

        if(row_data['NAME'] != '') {
            html += '<td nowrap="nowrap" valign="top">' + row_data['NAME'] + '</td>';
        }

        if(row_data['STATUS'] != '' && typeof row_data['STATUS'] !== 'undefined'){
            html += '<td nowrap="nowrap" valign="top">' + row_data['STATUS'] + '</td>';
        }else{
            html += '<td nowrap="nowrap" valign="top"></td>';
        }

        if(row_data['CONTACT_NAME'] != ''){
            html += '<td valign="top">'+ row_data['CONTACT_NAME'] + '</td>';
        }

        if(row_data['DATE'] != ''){
            html += '<td nowrap="nowrap" valign="top"><img src="include/images/blank.gif" width="3" height="1" alt="">' + row_data['DATE'] +'</td>';
        }

        html += '</tr>';


        if(row_data['DESCRIPTION'] != ''){

            html += '<tr class="oddListRowS1">' +
                '<td colspan="1" valign="top"></td>' +
                '<td colspan="4" valign="top">' +
                '<table><tr class="{ROW_COLOR}S1"><td valign="top"><img src="include/images/blank.gif" width="3" height="1" alt=""></td><td valign="top">' + row_data['DESCRIPTION'] + '</td></tr></table>' +
                '</td>' +
                '</tr>';
        }

        $('.list.view').append(html);

    }

};


