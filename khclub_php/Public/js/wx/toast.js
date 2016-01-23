var intervalCounter = 0;
function hideToast()
{
    var alert = document.getElementById("toast");
    alert.style.opacity = 0;
    clearInterval(intervalCounter);
}

function drawToast(message)
{
    var toast = document.getElementById("toast");
    if (toast == null)
    {
        var toastHTML = '<div id="toast">' + message + '</div>';
        document.body.insertAdjacentHTML('beforeEnd', toastHTML);
    }
    else
    {
        toast.style.opacity = .9;
        toast.innerHTML = message;
    }
    intervalCounter = setInterval("hideToast()", 1000);
}