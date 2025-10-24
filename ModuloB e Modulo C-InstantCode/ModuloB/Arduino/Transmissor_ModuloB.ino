// ============================================================================
// MÓDULO SENSOR / NÓ ÚNICO (TRANSMITE DADOS E RECEBE COMANDOS)
// ============================================================================

#include <DHT.h>
#include <Servo.h>
#include <SPI.h>
#include <nRF24L01.h>
#include <RF24.h>

// =================== CONFIGURAÇÕES DE PINOS (ATUALIZADAS) =====================
#define DHT_PIN 6      // Pino de dados do DHT11
#define DHT_TYPE DHT11 // Tipo de sensor
#define SERVO_PIN 8    // Pino de controle do Servo Motor (Esteira)
#define BUZZER_PIN 7   // Pino do Buzzer Ativo (Alarme)
#define CE_PIN 9       // Chip Enable NRF24L01
#define CSN_PIN 10     // Chip Select NRF24L01
// SPI: MOSI=11, MISO=12, SCK=13 (fixos)

// ======================= PARÂMETROS =============================
const float TEMP_CRITICA = 30.0; // Limite crítico para a temperatura
const long INTERVALO_LEITURA = 5000; // Intervalo de transmissão (5 segundos)
const char ID_SENSOR = '1';

// Endereços de Comunicação (devem bater com a central)
const byte EnderecoEscrita[6] = "CENTRAL"; // Nó escreve para CENTRAL
const byte EnderecoLeitura[6] = "NODE1";   // Nó lê comandos de NODE1

// ========================= ESTRUTURAS ===========================
// Dados enviados para a Central
struct DadosSensor {
  char idSensor;
  float temperatura;
  float umidade;
  bool motorLigado;
  bool alarmeAtivo;
};

// Comandos recebidos da Central
struct ComandoCentral {
  char idDestino;
  bool ligarMotor;
  bool ligarAlarme;
};

// ======================== OBJETOS E ESTADO ======================
DHT dht(DHT_PIN, DHT_TYPE);
Servo servo;
RF24 radio(CE_PIN, CSN_PIN);

unsigned long ultimaLeitura = 0;
bool estadoMotor = true;  // Começa LIGADO
bool estadoAlarme = false; // Começa DESLIGADO

// Para o movimento do servo
int posicaoServo = 0;
int direcaoServo = 1;

// ============================ SETUP =============================
void setup() {
  Serial.begin(9600);
  Serial.println("Iniciando No Sensor Único (ID 1)");
  
  // Inicialização Periféricos
  dht.begin();
  servo.attach(SERVO_PIN);
  servo.write(0);
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);
  
  // Inicialização NRF24L01
  if (!radio.begin()) {
    Serial.println("ERRO: Falha no NRF24L01! Verifique fiação e 3.3V.");
    while (1);
  }
  
  // Configurações NRF24L01
  radio.setPALevel(RF24_PA_MAX);
  radio.setDataRate(RF24_250KBPS);
  radio.setChannel(108);
  radio.setAutoAck(true);
  radio.enableAckPayload();
  
  // Pipes
  radio.openWritingPipe(EnderecoEscrita);   // Para enviar dados
  radio.openReadingPipe(1, EnderecoLeitura); // Para receber comandos
  
  // ESTABELECIMENTO DA COMUNICAÇÃO (UMA VEZ)
  radio.startListening(); // Entra no modo de escuta como estado padrão
  Serial.println("NRF24L01 OK. Modo de escuta ativo para comandos.");
}

// ============================= LOOP ===============================
void loop() {
  // 1. LÓGICA LOCAL E MOVIMENTO DO MOTOR (Roda a todo momento)
  executarLogicaLocal();
  
  // 2. VERIFICAÇÃO DE COMANDOS REMOTOS (Roda a todo momento no modo escuta)
  if (radio.available()) {
    receberComando();
  }
  
  // 3. LEITURA, ATUALIZAÇÃO DE ESTADO E TRANSMISSÃO (Roda a cada 5s)
  unsigned long tempoAtual = millis();
  if (tempoAtual - ultimaLeitura >= INTERVALO_LEITURA) {
    transmitirDados();
    ultimaLeitura = tempoAtual;
    
    // Volta imediatamente para o modo de escuta após a transmissão
    radio.startListening(); 
  }
}

// ======================= FUNÇÕES AUXILIARES ======================

void executarLogicaLocal() {
  // Movimento da Esteira (Servo)
  if (estadoMotor) {
    // Simula o movimento constante da esteira entre 0 e 180 graus
    posicaoServo += direcaoServo * 2;
    if (posicaoServo >= 180 || posicaoServo <= 0) {
      direcaoServo *= -1;
    }
    servo.write(posicaoServo);
  } else {
    // Se estiver PARADO, mantém a última posição
    servo.write(posicaoServo); 
  }
  
  // Controle do Alarme (Buzzer)
  digitalWrite(BUZZER_PIN, estadoAlarme ? HIGH : LOW);
  
  delay(15); // Pequeno delay para suavizar o movimento do servo
}

void receberComando() {
  ComandoCentral comando;
  radio.read(&comando, sizeof(comando));
  
  // O comando só é válido se o ID for '1' (ou 'A' se fosse multi-nó)
  if (comando.idDestino == ID_SENSOR) {
    
    // Tratamento de comandos LIGAR_MOTOR / DESLIGAR_MOTOR
    if (comando.ligarMotor != estadoMotor) {
      estadoMotor = comando.ligarMotor;
      Serial.print("COMANDO: Motor ");
      Serial.println(estadoMotor ? "LIGADO (remoto)" : "DESLIGADO (remoto)");
    }
    
    // Tratamento de comandos ALARME_ON / ALARME_OFF
    if (comando.ligarAlarme != estadoAlarme) {
      estadoAlarme = comando.ligarAlarme;
      Serial.print("COMANDO: Alarme ");
      Serial.println(estadoAlarme ? "ATIVADO (remoto)" : "DESATIVADO (remoto)");
    }
  }
}

void lerSensoresEAtualizarEstado() {
  float umidade = dht.readHumidity();
  float temperatura = dht.readTemperature();
  
  if (isnan(umidade) || isnan(temperatura)) {
    Serial.println("ERRO: Falha na leitura do DHT11.");
    return;
  }
  
  // Lógica de segurança automática baseada na temperatura
  if (temperatura > TEMP_CRITICA) {
    if (estadoMotor) {
      estadoMotor = false;
      Serial.println("ALERTA: Temp crítica! Motor PARADO.");
    }
    if (!estadoAlarme) {
      estadoAlarme = true;
      Serial.println("ALERTA: Temp crítica! Alarme ATIVADO.");
    }
  } else {
    if (!estadoMotor) {
      estadoMotor = true;
      Serial.println("OK: Temp normal. Motor LIGADO.");
    }
    if (estadoAlarme) {
      estadoAlarme = false;
      Serial.println("OK: Temp normal. Alarme DESATIVADO.");
    }
  }
}

void transmitirDados() {
  // 1. Atualiza estado com sensores
  lerSensoresEAtualizarEstado();
  
  // 2. Prepara dados para envio
  // Lemos a temperatura e umidade ANTES de chamar radio.stopListening()
  float temperatura = dht.readTemperature();
  float umidade = dht.readHumidity();
  
  DadosSensor dados = {ID_SENSOR, temperatura, umidade, estadoMotor, estadoAlarme};
  
  radio.stopListening(); // MODO TRANSMISSÃO
  
  // Imprime os dados para a SERIAL em um formato que o Python entenda
  // O Python espera 'Temp:' e 'Umid:' em linhas separadas.
  Serial.print("Temp: ");
  Serial.println(temperatura, 2); // Imprime com 2 casas decimais e NEWLINE
  
  Serial.print("Umid: ");
  Serial.println(umidade, 2); // Imprime com 2 casas decimais e NEWLINE

  if (radio.write(&dados, sizeof(dados))) {
    Serial.println("STATUS NRF: Enviado com sucesso.");
  } else {
    Serial.println("STATUS NRF: ERRO na transmissão!");
  }
}

// O restante do código Arduino (setup, loop e outras funções) permanece o mesmo.