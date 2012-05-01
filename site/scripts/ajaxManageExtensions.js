//handle the click on the "update" button that adds an extension
$(document).ready(function(){
    function doUpdate(){
        var url = $("#updateExtensionInput").val()
        $("#updateExtensionInput").css("background", "url('extensions/themes/silverblue/images/spinner.gif') no-repeat center white").attr('disabled','disabled');
        if(url != "" && url != $("#updateExtensionInput").attr("title")){
            $.ajax({
                type: 'POST',
                url: "reposerver/update",
                data: {"url": url },
                success: function(data, status){
                    $("#updateExtensionInput").css("background", 'white').removeAttr('disabled');
                    if(data.status){
                        alert("extension registered/updated")
                        //reset input field
                        $("#updateExtensionInput").val($("#updateExtensionInput").attr("title"))
                    } else {
                        alert(data.message)
                    }
                },
                error: function (){
                    $("#updateExtensionInput").css("background", 'white').removeAttr('disabled');
                     alert("unexpected response")
                },
                dataType: "json"
            });
        }
    }
    $("#updateExtensionInput").keypress(function(e){
      if(e.which == 13){
        //submit on enter
        doUpdate();
      }
    });
    $("#updateExtensionButton").click(doUpdate)
})