#!/bin/sh
# Uso: ./aprobados_elearning.sh /ruta_logs 15
# Donde 15 es la cantidad de hilos (lotes)

echo "Ejecutando script Aprobados Elearning $2 veces en la ruta: $1"

# Obtener total, minid y maxid solo una vez
DATA=$(php /miscursosprod45/moodle/miscursosprod/local/pubsub/cli/aprobados_total.php)
TOTAL=$(echo $DATA | cut -d'|' -f1)
MIN_ID=$(echo $DATA | cut -d'|' -f2)
MAX_ID=$(echo $DATA | cut -d'|' -f3)

echo "Total de registros: $TOTAL"
echo "Rango de IDs: $MIN_ID - $MAX_ID"

if [ -z "$TOTAL" ] || [ "$TOTAL" -eq 0 ]; then
  echo "No hay registros pendientes. Saliendo..."
  exit 0
fi

# Crear carpeta de logs si no existe
mkdir -p $1

# Ejecutar en paralelo cada lote
for i in $(seq 1 1 $2)
do   
   echo "Lanzando lote $i..."
   php /miscursosprod45/moodle/miscursosprod/local/pubsub/cli/aprobados_elearning.php \
       --lote=$i \
       --total=$2 \
       --count=$TOTAL \
       --minid=$MIN_ID \
       --maxid=$MAX_ID > $1/enviados$i.txt &
done

echo "Lotes enviados en paralelo. Esperando finalización..."
wait
echo "Fin de la ejecución."
