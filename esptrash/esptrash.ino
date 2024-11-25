#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <ESP32Servo.h>
#include <map>  // Include the map library to store points for different students
#include <WiFi.h>
#include <HTTPClient.h>

#define Inductive_Pin 32   // Metal waste
#define Capacitive_Pin 33  // Non-metal waste
#define Rain_Pin 25        // Wet waste
#define Moisture_Pin 26    // Dry waste
#define Infared_Pin 27     // General trash / Trash full detection

#define SS_PIN 5
#define RST_PIN 22

MFRC522 mfrc522(SS_PIN, RST_PIN);
Servo Servo;

// Points and student tracking
String Student = "";
String detectedTrash = "";  // Store detected trash type
bool rfidScanned = false;   // Track if RFID is scanned

// Map to store points for each student (studentID -> points)
std::map<String, int> studentPoints;

const char* ssid = "racist ka if nag connect ka";
const char* password = "12340986";

const char* serverName = "http://192.168.45.64/webAppTrashRecorder/dataProcess.php";

void setup() {
  Serial.begin(115200);
  Servo.attach(90);
  // Initialize SPI communication and RFID reader
  SPI.begin();
  mfrc522.PCD_Init();
  Serial.println("Tap your RFID card to register...");

  // Set sensor pins as input
  pinMode(Inductive_Pin, INPUT);
  pinMode(Capacitive_Pin, INPUT);
  pinMode(Rain_Pin, INPUT);
  pinMode(Moisture_Pin, INPUT);
  pinMode(Infared_Pin, INPUT);

  // Set the LED pin as output
  pinMode(LED_BUILTIN, OUTPUT);

  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.println("Connecting to WiFi...");
  }
  Serial.println("Connected to WiFi");
}

void loop() {
  // First, scan the RFID card to register the student
  if (!rfidScanned) {
    rfidScanned = checkRFID();
  }

  // After scanning the RFID, detect the trash and assign points
  if (rfidScanned && detectedTrash.equals("")) {
    Serial.println("Please throw the trash in the correct bin...");
    detectTrash();

    // If trash is detected, assign points and reset
    if (!detectedTrash.equals("")) {
      evaluatePoints();     // Assign points for correct bin usage
      rfidScanned = false;  // Reset RFID scanned status for the next student
      detectedTrash = "";   // Reset trash detection
    }
  }
}

void detectTrash() {
  bool metalDetected = digitalRead(Inductive_Pin);
  bool nonMetalDetected = digitalRead(Capacitive_Pin);
  bool wetDetected = digitalRead(Rain_Pin);
  bool dryDetected = digitalRead(Moisture_Pin);

  // Detect the type of trash thrown
  if (metalDetected) {
    detectedTrash = "Metal Waste";
    Serial.println("Trash detected: Metal Waste");

  } else if (nonMetalDetected) {
    detectedTrash = "Non-Metal Waste";
    Serial.println("Trash detected: Non-Metal Waste");

  } else if (wetDetected) {
    detectedTrash = "Wet Waste";
    Serial.println("Trash detected: Wet Waste");

  } else if (dryDetected) {
    detectedTrash = "Dry Waste";
    Serial.println("Trash detected: Dry Waste");
  }
}

bool checkRFID() {
  // Check for RFID card presence and read it
  if (!mfrc522.PICC_IsNewCardPresent()) return false;
  if (!mfrc522.PICC_ReadCardSerial()) return false;

  // Show RFID ID on serial monitor
  String studentID = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    studentID += String(mfrc522.uid.uidByte[i], HEX);
  }

  Student = studentID;

  // Initialize points for new students if not already present
  if (studentPoints.find(Student) == studentPoints.end()) {
    studentPoints[Student] = 0;  // Set initial points to 0 for new students
  }

  Serial.println("RFID detected: " + studentID);
  Serial.println("Current points: " + String(studentPoints[studentID]));
  Serial.println("Please throw the trash in the correct bin...");

  return true;
}

void evaluatePoints() {
  // Compare detected trash with the correct bin sensor and award points
  if (detectedTrash.equals("Metal Waste") && checkCorrectBin("Inductive_Pin")) {
    studentPoints[Student] += 10;
    sendDataToServer(Student, 10);
    digitalWrite(LED_BUILTIN, HIGH);  // Correct Bin
    Servo.write(180);
    Serial.println("Correct! +10 points! Total points: " + String(studentPoints[Student]));
    delay(5000);

  } else if (detectedTrash.equals("Non-Metal Waste") && checkCorrectBin("Capacitive_Pin")) {
    studentPoints[Student] += 10;
    sendDataToServer(Student, 10);
    digitalWrite(LED_BUILTIN, HIGH);  // Correct Bin
    Serial.println("Correct! +10 points! Total points: " + String(studentPoints[Student]));
    Servo.write(180);
    delay(5000);

  } else if (detectedTrash.equals("Wet Waste") && checkCorrectBin("Rain_Pin")) {
    studentPoints[Student] += 10;
    sendDataToServer(Student, 10);
    digitalWrite(LED_BUILTIN, HIGH);  // Correct Bin
    Serial.println("Correct! +10 points! Total points: " + String(studentPoints[Student]));
    Servo.write(180);
    delay(5000);

  } else if (detectedTrash.equals("Dry Waste") && checkCorrectBin("Moisture_Pin")) {
    studentPoints[Student] += 10;
    sendDataToServer(Student, 10);
    digitalWrite(LED_BUILTIN, HIGH);  // Correct Bin
    Serial.println("Correct! +10 points! Total points: " + String(studentPoints[Student]));
    Servo.write(180);
    delay(5000);

  } else {
    digitalWrite(LED_BUILTIN, LOW);  // Wrong Bin
    Serial.println("Incorrect bin. No points awarded.");
    Servo.write(90);  // Close the servo to prevent wrong trash entry
    delay(5000);
  }

  delay(5000);  // Prevent immediate re-detection
}

bool checkCorrectBin(String correctSensor) {
  // Check if the correct sensor was activated
  if (correctSensor == "Inductive_Pin" && digitalRead(Inductive_Pin)) return true;
  if (correctSensor == "Capacitive_Pin" && digitalRead(Capacitive_Pin)) return true;
  if (correctSensor == "Rain_Pin" && digitalRead(Rain_Pin)) return true;
  if (correctSensor == "Moisture_Pin" && digitalRead(Moisture_Pin)) return true;

  return false;  // If no correct bin was detected
}

void sendDataToServer(String rfid_id, int points) {
     if (WiFi.status() == WL_CONNECTED) {
       HTTPClient http;
       
       Serial.print("Connecting to: ");
       Serial.println(serverName);
       
       http.begin(serverName);
       http.addHeader("Content-Type", "application/x-www-form-urlencoded");

       String httpRequestData = "rfid_id=" + rfid_id + "&points=" + String(points);
       Serial.print("Sending data: ");
       Serial.println(httpRequestData);
       
       int httpResponseCode = http.POST(httpRequestData);

       if (httpResponseCode > 0) {
         String response = http.getString();
         Serial.println("HTTP Response code: " + String(httpResponseCode));
         Serial.println(response);
       } else {
         Serial.print("Error code: ");
         Serial.println(httpResponseCode);
       }
       http.end();
     } else {
       Serial.println("WiFi Disconnected");
     }
   }
   