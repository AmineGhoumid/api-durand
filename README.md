# API-Durand
API-Durand est une API REST permettant l'envoi de requêtes sécurisées via l'utilisation de JWT tokens. L'entreprise DURAND SAS a donc une API de gestion de base de données sécurisée.

## Installation

- Clonez le projet et installer les dépendances grâce à [composer](https://getcomposer.org/) :

```bash
composer install
composer update
```

- Mettez en place votre environnement composé d'un serveur web, de PHP 7.2.19 et de MySQL 5.7.24.

  Vous pouvez aussi utiliser un environnement de stack comme [Laragon](https://laragon.org/).
  
- Importer la base de données présente à la racine du projet.

- Pour la sécurisation via JWT il faudra créer une clé privée et publique permettant la création et la 
  vérification des tokens entrants. Le pass à fournir doit être gardé confidentiel et est impératif pour la configuration du .env dans l'étape suivante.
```bash
openssl genrsa -out config/jwt/private.pem -aes256 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

- Il vous faudra ensuite configurer votre fichier .env en prenant exemple sur la configuration de développement.

- Finalement vous aurez besoin d'un client REST: [Postman](https://www.postman.com/) ou [Insomnia](https://insomnia.rest/) sont des exmples.




## Utilisation
Une fois votre installation effectuée, il faut lancer tous les services et préparer les requêtes HTTP qui sont au nombre de 6.

Les deux premières sont utiles pour la création d'un compte et la connexion qui en fait renvoi le token prouvant l'authentification. Les suivants utilisent le token présent dans le header des requêtes pour prouver l'identité de l'envoyeur et ainsi effectuer des actions sur la bdd.

Il faut dans les deux premiers cas fournir dans le header le Content-Type qui sera du JSON et dans les quatres autres il faudra ajouter dans le header un Authorization qui sera contenu dans le JWT token.

Voici des CURL en guise d'exemples :

```bash

curl -X POST -H "Content-Type: application/json" http://localhost:8002/api/register check -d '{"username": "myUserName", "password" : "myPassword"}'
curl -X POST -H "Content-Type: application/json" http://localhost:8002/api/login_check check -d '{"username": "myUserName", "password" : "myPassword"}'


curl -X POST -H "Content-Type: application/json; Authorization: BEARER [USERTOKEN]" http://localhost:8002/api/getUserMachines check -d '{"username": "myUserName"}'
curl -X POST -H "Content-Type: application/json; Authorization: BEARER [USERTOKEN]" http://localhost:8002/api/createMachine check -d '{"username": "myUserName", "machinename": "myMachineName", "description": "machineDescription"}'
curl -X POST -H "Content-Type: application/json; Authorization: BEARER [USERTOKEN]" http://localhost:8002/api/editMachine check -d '{"machineName": "machineName", "newMachineName": "newMachineName", "newDesscription": "newDesscription"}'
curl -X POST -H "Content-Type: application/json; Authorization: BEARER [USERTOKEN]" http://localhost:8002/api/deleteMachine check -d '{"machineName": "machineName"}'

```


## Créateur
Amine Ghoumid
