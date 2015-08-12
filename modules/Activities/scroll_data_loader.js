var container = $('body');
$(container).data("offset",0);

$(window).scroll(function() {
    if($(window).scrollTop() == $(document).height() - $(window).height()) {

        var offset = $(container).data("offset");

        $(container).data("offset",load_summary_data(offset));

    }
});


function load_summary_data(offset) {

    offset = offset + 80;

    $.ajax({
        method: "POST",
        url: "index.php?module=Activities&action=loadSummaryData",
        data: {'offset': offset, to_pdf: true},
        type:'json'
    }) .always(function( result ) {
        jQuery.each($.parseJSON(result), function(key, value) {
            append_data(value);
        });
    });

    return offset;
}

function append_data(row_data){

    console.log(row_data);


    var html =  '<tr height="20" class="oddListRowS1">';

    if(typeof row_data['MODULE'] != undefined) {
        html += '<td nowrap="nowrap" valign="top"><img src="themes/SuiteR/images/' + row_data['MODULE'] + '.gif"</td>';
    }

    if(typeof row_data['NAME'] != undefined) {
        html += '<td nowrap="nowrap" valign="top">' + row_data['NAME'] + '</td>';
    }

    if(typeof row_data['STATUS'] != undefined){
        html += '<td nowrap="nowrap" valign="top"></td>';
    }

    if(typeof row_data['CONTACT_NAME'] != undefined){
        html += '<td valign="top">'+ row_data['CONTACT_NAME'] + '</td>';
    }

    if(typeof row_data['DATE'] != undefined){
        html += '<td nowrap="nowrap" valign="top"><img src="include/images/blank.gif" width="3" height="1" alt="">' + row_data['DATE'] +'</td>';
    }

    html += '</tr>';

    $('.list.view').append(html);

}


