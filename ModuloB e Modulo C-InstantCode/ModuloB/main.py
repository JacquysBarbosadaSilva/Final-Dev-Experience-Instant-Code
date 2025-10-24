import serial
import csv
import sqlite3
from datetime import datetime
import time
import matplotlib.pyplot as plt
from matplotlib.animation import FuncAnimation
import pandas as pd
import threading

# ===========================
# CONFIGURAÇÕES
# ===========================
PORTA_SERIAL = 'COM6'  # Ajuste para sua porta
BAUD_RATE = 9600
ARQUIVO_CSV = 'dados_sensores.csv'
ARQUIVO_DB = 'dados_sensores.db'
ARQUIVO_XLS = 'dados_sensores.xlsx'
ID_NO = 'NODO1'  # Identificador do nó Arduino
LIMITE_GRAFICO = 20  # Quantos pontos exibir no gráfico

# Listas para gráfico
timestamps = []
temperaturas = []
umidades = []

# ===========================
# CONFIGURAÇÃO CSV
# ===========================
def criar_csv():
    try:
        with open(ARQUIVO_CSV, mode='x', newline='') as file:
            writer = csv.writer(file)
            writer.writerow(['timestamp', 'id_no', 'temperatura', 'umidade'])
            print(f"CSV '{ARQUIVO_CSV}' criado com sucesso!")
    except FileExistsError:
        print(f"CSV '{ARQUIVO_CSV}' já existe, adicionando dados...")

# ===========================
# CONFIGURAÇÃO SQLITE
# ===========================
def criar_db():
    conn = sqlite3.connect(ARQUIVO_DB)
    cursor = conn.cursor()
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS dados_sensores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            timestamp TEXT,
            id_no TEXT,
            temperatura REAL,
            umidade REAL
        )
    ''')
    conn.commit()
    conn.close()
    print(f"Banco de dados '{ARQUIVO_DB}' pronto!")

# ===========================
# EXPORTAR PARA XLS
# ===========================
def exportar_para_excel():
    try:
        df = pd.read_csv(ARQUIVO_CSV)
        df.to_excel(ARQUIVO_XLS, index=False)
        print(f"Relatório XLS atualizado: {ARQUIVO_XLS}")
    except Exception as e:
        print("Erro ao exportar para Excel:", e)

# ===========================
# FUNÇÃO PARA GRAVAR DADOS
# ===========================
def salvar_dados(temperatura, umidade):
    timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    
    # Salva CSV
    with open(ARQUIVO_CSV, mode='a', newline='') as file:
        writer = csv.writer(file)
        writer.writerow([timestamp, ID_NO, temperatura, umidade])
    
    # Salva SQLite
    conn = sqlite3.connect(ARQUIVO_DB)
    cursor = conn.cursor()
    cursor.execute('''
        INSERT INTO dados_sensores (timestamp, id_no, temperatura, umidade)
        VALUES (?, ?, ?, ?)
    ''', (timestamp, ID_NO, temperatura, umidade))
    conn.commit()
    conn.close()
    
    # Atualiza relatório XLS
    exportar_para_excel()
    
    print(f"[{timestamp}] Temp: {temperatura}°C, Umid: {umidade}%")
    
    # Atualiza listas para o gráfico
    timestamps.append(timestamp)
    temperaturas.append(temperatura)
    umidades.append(umidade)
    
    # Mantém apenas os últimos 20 registros no gráfico
    if len(timestamps) > LIMITE_GRAFICO:
        timestamps.pop(0)
        temperaturas.pop(0)
        umidades.pop(0)

# ===========================
# LEITURA SERIAL
# ===========================
# ===========================
# LEITURA SERIAL (CORRIGIDA)
# ===========================
def ler_serial():
    try:
        arduino = serial.Serial(PORTA_SERIAL, BAUD_RATE, timeout=1)
        time.sleep(2)
        print(f"Conectado à porta {PORTA_SERIAL}")
        
        # Variáveis temporárias para armazenar o último par de dados lido
        temperatura_lida = None
        umidade_lida = None
        
        while True:
            linha = arduino.readline().decode('utf-8').strip()
            
            if linha:
                # 1. Tenta ler a Temperatura
                if 'Temp:' in linha:
                    try:
                        # Extrai o valor float da linha "Temp: 25.50"
                        temp_str = linha.split('Temp:')[1].split('°')[0].strip()
                        temperatura_lida = float(temp_str)
                        # print(f"DEBUG: T={temperatura_lida}") # DEBUG
                    except Exception as e:
                        print("Erro ao processar temperatura:", linha, e)
                        continue
                        
                # 2. Tenta ler a Umidade
                elif 'Umid:' in linha:
                    try:
                        # Extrai o valor float da linha "Umid: 60.00"
                        umid_str = linha.split('Umid:')[1].split('%')[0].strip()
                        umidade_lida = float(umid_str)
                        # print(f"DEBUG: U={umidade_lida}") # DEBUG
                    except Exception as e:
                        print("Erro ao processar umidade:", linha, e)
                        continue
                
                # 3. Se ambos os valores foram lidos, salve o par completo
                if temperatura_lida is not None and umidade_lida is not None:
                    salvar_dados(temperatura_lida, umidade_lida)
                    
                    # Reset para aguardar o próximo par
                    temperatura_lida = None
                    umidade_lida = None
                
    except serial.SerialException as e:
        print("Erro ao conectar à porta serial:", e)
    except KeyboardInterrupt:
        print("Leitura interrompida pelo usuário")

# O restante do código Python (salvar_dados, criar_csv, etc.) permanece o mesmo.

# ===========================
# GRÁFICO EM TEMPO REAL
# ===========================
def atualizar_grafico(i):
    plt.cla()
    plt.plot(timestamps, temperaturas, label='Temperatura (°C)', color='red', marker='o')
    plt.plot(timestamps, umidades, label='Umidade (%)', color='blue', marker='x')
    plt.xticks(rotation=45, ha='right')
    plt.ylabel('Valores')
    plt.xlabel('Timestamp')
    plt.title(f'Dados do Sensor {ID_NO} (Últimos {LIMITE_GRAFICO} registros)')
    plt.legend()
    plt.tight_layout()

# ===========================
# PROGRAMA PRINCIPAL
# ===========================
if __name__ == '__main__':
    criar_csv()
    criar_db()
    
    thread_serial = threading.Thread(target=ler_serial, daemon=True)
    thread_serial.start()
    
    plt.figure(figsize=(10, 5))
    ani = FuncAnimation(plt.gcf(), atualizar_grafico, interval=2000)
    plt.show()
