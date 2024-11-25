document.getElementById('addCardButton').addEventListener('click', function() {
    document.getElementById('addCardPanel').style.display = 'none';
    document.getElementById('enterCardPanel').style.display = 'block';
});

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('addCardButton').addEventListener('click', function() {
        document.getElementById('addCardPanel').style.display = 'none';
        document.getElementById('enterCardPanel').style.display = 'block';
    });

    document.getElementById('enterCardButton').addEventListener('click', function() {
        const rfid = document.getElementById('cardInputField').value;
        if (rfid) {
            window.location.href = `viewCard.php?rfid_id=${encodeURIComponent(rfid)}`;
        } else {
            document.getElementById('errorMessage').textContent = 'RFID cannot be empty';
            document.getElementById('errorMessage').style.display = 'block';
        }
    });
});