# Repository technique de Psychonaut

*À l'occasion de la migration numéro trente huit mille cinq cent quatre vingts six.*

**📢 Attention :** les extraits de code présents dans ce fichier sont là à
titre d'illustration, et diffèrent du code utilisé en pratique. Pour avoir une
idée précise des choses qui se passent, il faudra plonger dans le vrai code !

**📢 Attention :** les chemins et URIs présentes dans ce document utilisent
pour l'instant le domaine du playground de tests, par celles du serveur de
production.

ℹ️ Les addons doivent être installés à partir des packages zip, par exemple
`Serotonin/_releases/Xyzt-Serotonin-1.0.0-dev.zip`, pas à partir du code
source !

## Cheat sheet

* Logs `nginx` :

  ```sh
  $> sudo cat /var/log/nginx/psychonaut.ynh.fr-error.log
   ```

* Recharger la config de `nginx` :

  ```sh
  $> sudo systemctl reload nginx.service
   ```

* Packager un addon Xenforo :

  ```sh
  $> sudo php /var/www/my_webapp/www/cmd.php xf-addon:build-release Xyzt/SuperAddonDeLaMort
   ```

## Configuration de `nginx`

Les configuration des autres services que Xenforo et consorts (PHPMyAdmin,
Cockpit...) ne sont pas présentes sur ce repo.

### `/etc/nginx/conf.d/psychonaut.ynh.fr.conf`

Configuration racine pour le domaine apex.

### `/etc/nginx/conf.d/psychonaut.ynh.fr.d/my_webapp.conf`

Paramètres généraux pour le logiciel Xenforo.

### `/etc/nginx/conf.d/psychonaut.ynh.fr.d/my_webapp.d/php.conf`

Configuration de PHP et lien avec `fastcgi` et `php-fpm`.

### `/etc/nginx/conf.d/psychonaut.ynh.fr.d/my_webapp.d/mybb-threads.conf`

Rewrite nécessaire au fonctionnenement de Kétamine, addon chargé de la
redirection des anciens liens de sujet.

## Redirection des anciens liens de topic *(car [les liens morts c'est l'enfer][1])*

Addon custom **Kétamine** exécuté en production.

### Prérequis

* **NE PAS NUKE** la table de logs issue de l'importation de MyBB !

* Importer l'ancienne table du plugin SEO `psy_google_seo` dans la DB sur
laquelle est déployée Xenforo.

### Synoptique

* Ancienne URI :

  ➡️ <https://www.psychonaut.fr/Thread-SOS-modo-battu-VS-Solidarit%C3%A9-drogu%C3%A9-r%C3%A9prim%C3%A9>

* `nginx` s'occupe de faire un rewrite, car en l'état actuel cette URL n'est
  pas routable par le router de Xenforo. Le rewrite est en gros sous la forme :

  ```nginx
  location /Thread- {
    rewrite ^/Thread-Jadore-me-droguer$ mybb-thread/Jadore-me-droguer/ permanent;
  }
  ```

  ➡️ <https://www.psychonaut.fr/mybb-thread/SOS-modo-battu-VS-Solidarit%C3%A9-drogu%C3%A9-r%C3%A9prim%C3%A9>

* Substring issue du router :

  ➡️ `SOS-modo-battu-VS-Solidarit%25C3%25A9-drogu%25C3%25A9-r%25C3%25A9prim%25C3%25A9`

* Percent-decode :

  ➡️ `SOS-modo-battu-VS-Solidarité-drogué-réprimé`

* Requête sur l'ancienne table du plugin SEO :

  ```sql
  SELECT `id`
  FROM `psy_google_seo`
  WHERE `url` = "SOS-modo-battu-VS-Solidarité-drogué-réprimé"
    AND `idtype` = 4
    AND `active` = 1;
  ```

  ➡️ `15674`

* Requête sur la table de logs d'importation :

  ```sql
  SELECT `new_id`
  FROM `import_log_psychonaut_2`
  WHERE `content_type` = "thread" AND `old_id` = 15674;
  ```
  
  ➡️ `15389`

* Requête sur la table de threads de Xenforo :

  ```sql
  SELECT * FROM `xf_thread` WHERE `thread_id` = 15389;
  ```

  ➡️ `15389  4  [SOS modo battu] VS [Solidarité drogué réprimé]  1561  307516  2876  Loutre  1285847487  1  visible  1  ...`

* Résolution d'URI par Xenforo, qui renvoie un code HTTP `301 moved
  permanently` :

  ➡️ <https://psychonaut.ynh.fr/index.php?threads/sos-modo-battu-vs-solidarite-drogue-reprime.15389/>

* Après rewrite par `nginx` :

  ➡️ <https://psychonaut.ynh.fr/threads/sos-modo-battu-vs-solidarite-drogue-reprime.15389/>

## Importation des likes *(donnez moi de la reconnaissance)*

Addon **Sérotonine** a exécuter une seule fois.

### Prérequis

* **NE PAS NUKE** la table de logs issue de l'importation de MyBB !

* Avoir l'ancienne table du plugin de likes
`psy_g33k_thankyoulike_thankyoulike` accessible au moment de lancer le script.
Elle peut être supprimée après.

* Se préparer à un downtime de quelques minutes.

### Synoptique

* ```sql
  SELECT `pid`, `uid`, `dateline`
  FROM `psy_g33k_thankyoulike_thankyoulike`
  ORDER BY `psy_g33k_thankyoulike_thankyoulike`.`tlid` DESC;
  ```

  ```txt
  655328  1317  1702746012
  655327  5699  1702739963
  655271  1317  1702732244
  ...
  ```

* ```sql
  SELECT `new_id`
  FROM `import_log_psychonaut_2`
  WHERE `content_type`="post" AND `old_id`=655328;
  ```
  
  `629981`

* ```sql
  SELECT `new_id`
  FROM `import_log_psychonaut_2`
  WHERE `content_type`="user" AND `old_id`=1317;
  ```
  
  `1317`

  Attention, des fois la requête ne retournera rien car il y a des users qui
  sont passés à la trappe lors de l'importation somehow...

* Préparer ses jouets :

  ```php
  protected function getReactionRepo()
  {
    return \XF::app()->em()->getRepository('XF:Reaction');
  }

  protected function findUser($id)
  {
    return \XF::finder('XF:User')->where('user_id', $id)->fetchOne();
  }

  $repo = getReactionRepo();
  ```

* Pour la réaction :

  ```php
  $post_id = 629981;
  $user_id = 1317;

  // Cf. \XF\Repository\Reaction
  $repo.insertReaction(
    $reactionId = 1,
    $contentType = "post",  // Inchallah
    $contentId = $post_id,
    $reactUser = findUser($user_id),
    $publish = false,
    $isLike = true
  );
  ```

* Et `$repo->rebuildReactionCache()` pour finir.

[1]: https://mixtures.info/fr/blog/article/des-sources-en-balle/
