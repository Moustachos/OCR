/*
SQLyog Ultimate v13.1.1 (64 bit)
MySQL - 5.7.24 : Database - news
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`news` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `news`;

/*Table structure for table `comments` */

DROP TABLE IF EXISTS `comments`;

CREATE TABLE `comments` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `news` smallint(6) NOT NULL,
  `auteur` varchar(50) NOT NULL,
  `contenu` text NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

/*Data for the table `comments` */

insert  into `comments`(`id`,`news`,`auteur`,`contenu`,`date`) values 
(1,1,'Bar','Nulla ullamcorper massa eget mi aliquam, ut feugiat nisl iaculis. Morbi in sagittis lectus, eu varius metus. Proin cursus semper dui vel feugiat. Suspendisse ac aliquam ex. Fusce velit lectus, consectetur eget urna eu, tempor porttitor magna. Mauris porta risus metus, eget tempor orci tempor et. Curabitur faucibus aliquam elit, in dapibus nisi hendrerit sit amet.\r\n\r\nCras ac metus ut ex pellentesque ullamcorper. Fusce nibh ex, volutpat vestibulum sapien id, aliquam accumsan nisl. Mauris convallis eget leo ac gravida.','2019-03-12 10:47:11'),
(2,2,'Admin','Ceci est un commentaire','2019-03-12 10:48:22');

/*Table structure for table `news` */

DROP TABLE IF EXISTS `news`;

CREATE TABLE `news` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `auteur` varchar(30) NOT NULL,
  `titre` varchar(100) NOT NULL,
  `contenu` text NOT NULL,
  `dateAjout` datetime NOT NULL,
  `dateModif` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

/*Data for the table `news` */

insert  into `news`(`id`,`auteur`,`titre`,`contenu`,`dateAjout`,`dateModif`) values 
(1,'Foo','Hello world','\r\n\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi quis ullamcorper libero. Nam pulvinar aliquet placerat. Donec mollis enim diam. Vivamus faucibus ornare metus sed venenatis. Fusce sit amet augue rutrum, scelerisque massa pretium, aliquam sapien. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed vel nisl ac ante dignissim pharetra. Praesent ut lorem euismod, pretium orci sed, volutpat ligula. Nunc id tincidunt diam. Suspendisse vitae elementum ipsum. Mauris gravida hendrerit libero, vitae sagittis est venenatis at. Nunc vestibulum, lacus vel sollicitudin consectetur, ex nulla vulputate mauris, sit amet ultrices lorem quam nec augue.\r\n\r\nDonec posuere risus vel urna efficitur blandit. Sed gravida libero at turpis hendrerit tincidunt. Aliquam tempor auctor hendrerit. Nam id viverra libero. Fusce pharetra suscipit aliquam. Ut semper ullamcorper felis at vehicula. Maecenas non nunc eu diam pellentesque fermentum. Mauris sit amet molestie magna, eget ultrices ligula. Fusce nisi metus, malesuada ac felis id, posuere blandit erat. Etiam accumsan arcu quis tempus rutrum. Phasellus eget pharetra enim.\r\n\r\nFusce varius felis sed commodo condimentum. Sed at fringilla orci. Pellentesque lacus quam, tincidunt sed congue eu, mattis ac erat. Donec non turpis purus. Donec laoreet leo sed purus pretium mollis. Maecenas varius odio eu sapien varius, quis lobortis felis blandit. Fusce tellus augue, pellentesque non bibendum vitae, fermentum vel dolor. Phasellus nulla massa, aliquet ac lacus vitae, tempor efficitur orci. Duis vulputate metus vitae eros sodales, pretium venenatis ipsum dignissim. Quisque eu eros odio. Nullam non ex sed lectus dictum scelerisque eget non lorem. Suspendisse a risus sed tellus commodo pellentesque nec vel purus.\r\n\r\nDonec pellentesque eros risus, ac lobortis tortor tempor at. Suspendisse ultrices felis vitae ipsum vestibulum, a scelerisque lectus malesuada. Nulla condimentum nec tortor id pretium. Nam venenatis placerat ante in tempor. Nam pharetra pharetra mi, non euismod sapien interdum at. Duis efficitur mauris eget dui mattis maximus. Duis vulputate tellus ipsum, sit amet porta lacus iaculis in. Vestibulum id nisi metus. Fusce dignissim placerat porttitor. Nulla at libero in felis rutrum dapibus at pretium magna. Integer nisi elit, rutrum ac consequat nec, egestas non metus. Suspendisse tempus pharetra libero. In porta rutrum sollicitudin. Fusce et odio sapien. Suspendisse posuere, massa quis luctus posuere, urna enim tempus diam, in vestibulum nisl augue eu est.\r\n\r\nAliquam vitae rhoncus neque. Nulla facilisi. Aenean a erat eget metus scelerisque egestas vitae nec nisl. Quisque interdum ullamcorper consectetur. Vestibulum dignissim quam dolor, et pellentesque diam pulvinar sit amet. Integer tortor tortor, pulvinar sed metus in, ullamcorper cursus tortor. Ut leo orci, maximus in egestas sed, viverra vitae purus. Nam rhoncus, arcu ut dictum convallis, nibh dolor consectetur felis, ut aliquam mauris arcu a elit. ','2019-03-12 10:46:40','2019-03-12 10:46:40'),
(2,'Admin','Ceci est une news','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed quis nisl id nisl tincidunt posuere a non odio. Cras cursus massa et velit euismod tincidunt. Vestibulum odio eros, condimentum at leo in, ornare blandit odio. Fusce quis pretium purus, nec convallis leo. Mauris at ligula ante. Vivamus molestie laoreet diam vel vehicula. Nulla at sapien id metus laoreet commodo. Nulla eleifend nibh id scelerisque volutpat. Proin ut finibus elit. Integer interdum accumsan lobortis. Vestibulum fringilla ex vel lorem blandit convallis.\r\n\r\nUt ac vestibulum mi. Vestibulum a enim eget lectus consequat volutpat. Fusce sodales dui non velit placerat, vel vulputate mauris mollis. Praesent eu est aliquam, aliquet est quis, laoreet dolor. Sed nec lacus pharetra, euismod ipsum non, suscipit leo. Curabitur vel diam dictum, euismod justo non, tempus leo. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Integer volutpat placerat mauris, aliquet facilisis nisi venenatis nec. Nulla convallis suscipit risus, sed accumsan turpis tincidunt id. Ut non varius libero. Mauris sit amet vulputate tellus. Nullam pretium metus sapien, vel eleifend leo auctor vitae. Vestibulum tortor nunc, gravida eu velit ut, viverra interdum tellus. Sed libero nulla, malesuada ut tristique vitae, blandit ut est. Proin ac ante et metus imperdiet tristique in nec massa.\r\n\r\nSuspendisse hendrerit molestie felis, at mattis lorem. Ut sodales magna in libero blandit auctor. Maecenas sed erat eget orci pretium pellentesque. Nulla interdum malesuada orci, vitae molestie augue pharetra non. Sed posuere enim id risus tristique, ut egestas quam convallis. Pellentesque fringilla, risus ut faucibus iaculis, ipsum mi pulvinar diam, elementum tristique lacus dolor ornare ipsum. Morbi convallis rutrum tortor. Maecenas eu mollis nibh, et laoreet sapien. Praesent tincidunt sapien eu ipsum lobortis lobortis. ','2019-03-12 10:48:02','2019-03-12 10:48:02');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
