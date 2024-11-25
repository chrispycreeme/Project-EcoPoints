document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('enterCardButton').addEventListener('click', function() {
        const rfid = document.getElementById('cardInputField').value;
        validateRFID(rfid);
    });

    function validateRFID(rfid) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "dataProcess.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        document.getElementById('errorMessage').style.display = 'block';
                    } else {
                        document.getElementById('errorMessage').style.display = 'none';
                    }
                } else {
                    document.getElementById('errorMessage').style.display = 'block';
                }
            }
        };
        xhr.send("rfid_id=" + encodeURIComponent(rfid) + "&validate=true");
    }
});