# EXERCICES OPENCLASSROOMS
Ce dépôt regroupe des projets créés dans le cadre d'activités / exercices de cours que j'ai suivis sur la plateforme de formation OpenClassrooms.

### POO PHP - Creer un systeme de mise en cache ###
#### Cours correspondant ####
https://openclassrooms.com/fr/courses/1665806-programmez-en-oriente-objet-en-php

#### Objectifs de l'exercice ####
Étendre l'application OCFram (développée lors du cours) en créant un système de mise en cache des données (commentaires / news) et des vues.

#### Consignes ####
- le système de cache doit être développé en orienté objet
- deux classes distinctes doivent être créées pour gérer les deux types de cache
- le code produit doit respecter le principe d'encapsulation
- lorsqu'une vue est disponible en cache, le contrôleur correspondant ne doit pas être exécuté
- les commentaires correspondant à une news doivent être stockés sous forme de liste (tous les commentaires dans un seul fichier)
- la première ligne d'un fichier de cache doit correspondre au timestamp d'expiration de ce cache
- une ressource doit être mise en cache lors de sa première consulation, pas lors de sa création
- le cache correspondant à une ressource doit être supprimé lorsque :
-> le cache arrive à expiration
-> la ressource en question (donnée ou vue) est modifiée ou supprimée

#### Comment le tester ? ####
Une fois le dépôt cloné :
- la structure et les données de base pour la base de données se trouvent dans POO PHP - Creer un systeme de mise en cache/db_structure.sql
- pour que l'application fonctionne, vous devez configurer un serveur web dont la racine pointe vers le dossier Web/
- vous pouvez accéder à l'espace d'administration avec l'identifiant "admin" et le mot de passe "mdp" en rajoutant admin/ à l'adresse que vous aurez choisie pour le serveur web, cela vous permettra de gérer les news et de modifier / supprimer les commentaires

Notes:
- vous pouvez modifier l'identifiant et le mot de passe pour accéder à l'administration dans POO PHP - Creer un systeme de mise en cache/App/Backend/Config/app.xml
- la configuration du système de cache se fait via le fichier POO PHP - Creer un systeme de mise en cache/App/Frontend/Config/cache.xml
- les fichiers de cache sont stockés dans le dossier tmp/datas pour les données et tmp/views pour les vues (configurable)
- le cache des données est sérialisé et est enregistré au format .txt (configurable)
- le cache des vues n'est pas sérialisé et est enregistré au format .html (configurable)
- le système peut facilement être maintenu et étendu (ajout de nouveaux types de cache) grâce au chargement automatique de tous les types de cache spécifiés dans le fichier de configuration
- l'intégrité de tous les paramètres spécifiés dans le fichier de configuration du cache est vérifiée au chargement de l'application
