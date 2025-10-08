import RPi.GPIO as GPIO
import time
import sys

# Fonction pour ouvrir le portail pendant X secondes
def open_gate(duration=10):
    # Configuration GPIO
    GPIO.setmode(GPIO.BCM)
    GPIO.setup(17, GPIO.OUT)

    # Ouvrir le portail
    print("Ouverture du portail...")
    GPIO.output(17, GPIO.HIGH)
    time.sleep(duration)

    # Fermer le portail
    print("Fermeture du portail...")
    GPIO.output(17, GPIO.LOW)

    # Nettoyage
    GPIO.cleanup()

    # Si le script est exécuté directement depuis la ligne de commande
if __name__ == "__main__":
    duration = 2  # valeur par défaut

    # Récupérer la durée passée en argument
    if len(sys.argv) > 1:
        try:
            duration = int(sys.argv[1])
        except ValueError:
            print("Argument non valide, utilisation de la valeur par d�faut (2s)")

    open_gate(duration)