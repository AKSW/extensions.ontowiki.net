//handle the click on the "update" button that adds an extension
$(document).ready(function(){
    $("#updateExtensionButton").click(function(){
        var url = $("#updateExtensionInput").val()
        if(url != "" && url != $("#updateExtensionInput").attr("title")){
            $.ajax({
                type: 'POST',
                url: "reposerver/update",
                data: {"url": url },
                success: function(data, status){
                    if(data.ok){
                        alert("extension registered")
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
    })
})