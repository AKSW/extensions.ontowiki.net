//handle the click on the "update" button that adds an extension
$(document).ready(function(){
    function doUpdate(){
        var url = $("#updateExtensionInput").val()
        if(url != "" && url != $("#updateExtensionInput").attr("title")){
            $.ajax({
                type: 'POST',
                url: "reposerver/update",
                data: {"url": url },
                success: function(data, status){
                    if(data.status){
                        alert("extension registered/updated")
                        //reset input field
                        $("#updateExtensionInput").val($("#updateExtensionInput").attr("title"))
                    } else {
                        alert(data.message)
                    }
                },
                error: function (){
                     alert("unexpected response")
                },
                dataType: "json"
            });
        }
    }
    $("#updateExtensionInput").keypress(function(e){
      if(e.which == 13){
        doUpdate();
      }
      e.preventDefault();
      return false;
    });
    $("#updateExtensionButton").click(doUpdate)
})