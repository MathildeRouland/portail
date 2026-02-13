import lgpio
import time

# Numéro de GPIO (BCM)
PIN = 17

# Fonction pour ouvrir le portail pendant X secondes
def open_gate(duration=10):
  # Ouvre la puce GPIO 0 (le contrôleur principal)
    h = lgpio.gpiochip_open(0)

    # Déclare la broche en sortie
    lgpio.gpio_claim_output(h, PIN)

    print("Ouverture du portail...")
    lgpio.gpio_write(h, PIN, 1)
    time.sleep(duration)

    print("Fermeture du portail...")
    lgpio.gpio_write(h, PIN, 0)

    # Libère la ressource
    lgpio.gpiochip_close(h)
    print("Test terminé.")

if __name__ == "__main__":
    open_gate()