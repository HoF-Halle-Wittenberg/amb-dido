---
Plugin Name: AMB-DidO Plugin 
Description: Erstellt Metadaten gemäß AMB-Standard im JSON-Format für didaktische und Organisationsressourcen auf beliebigen Wordpress-Seiten.
Latest Release: 0.8.5
Author: Justus Henke (HoF), Manuel Oellers (Contributor, U Münster) 
---
# AMB-DidO Plugin 


 
Dies ist ein Wordpress-Plugin zur Erstellung von Metadaten für didaktische und Organisationsressourcen angelehnt an das Allgemeines Metadatenprofil für Bildungsressourcen (AMB).

Weitere Informationen zum AMB: https://dini-ag-kim.github.io/amb/latest/

Kontakt und Feedback sowie Feature-Requests: opendata@hof.uni-halle.de

## Status 
Aktuell noch in Entwicklung, aber bereits funktionsfähig zum Testen.

## Integrierte Features
- Editor: Auswählen von Metadaten (Werte und offene Felder)
- Quellcode: Erstellen des JSON-LD Skripts im Quellcode
- Optimierung: Import öffentlicher Wertelisten/Vokabulare (AMB, LRMI, schema.org) 
- Optionen: Einstellen der Seitentypen, in denen Metadaten aktiviert sind
- Optionen: Einstellen von Standard-Werten und ausblenden dieser Felder im Editor
- Optionen: Ausgabe von Metadaten im Frontend (unterhalb Content)
- Optionen: Einstellen welche Metadaten im Frontend angezeigt werden sollen
- Frontend: Hook-Function und Shortcode für einbau beliebiger Metadaten im Frontend
- Optimierung: Integration eigener veröffentlichter Wertelisten/Vokabulare
- Optionen: bestimmte Felder deaktivieren statt Standardwert festzulegen.
- Optimierung: Integration von Mehrebenen-Vokabularen in die Standardfunktion zur Generierung der Metadatenfelder
- Optimierung: Integration der Metadaten in die Wordpress Suchfunktion
- Frontend: Werte aus Vokabularen klickbar machen und in Ergebnisliste
- Optionen: Zusätzliche selbstgewählte Wertelisten aus Archiven abrufen und integrieren.
- Optionen: Vorhandene Wordpress-Taxonomien für AMB-Felder nutzen (und Überbrückung des Metafeldes) 
- Optionen: Standard-Wordpress-Felder für Keywords (tags) und Beschreibung (excerpt) nutzen statt Plugin-spezifischer
- Optionen: Darstellung der Options-Sektionen in Tabs
- Optionen: Labels der Wertelisten können nun überschrieben werden
- Optimierung: Darstellung von Taxonomien und Metafeldern mittels Shortcode
- Löschen hinzugefügter Wertelisten
- Optische Verbesserungen in den Einstellungen

## Features in Arbeit
- Editor/Minor: Creator-Feld als Wordpress-Taxonomie integrieren statt Freifeld.
- Frontend: Ausgabe von Keywords, Autoren und Beschreibung im Frontend.

## Features in Planung
- Optionen: Reihenfolge der Felder in Editor und Frontend einstellen
- Editor: Tooltips mit Beschreibungen der Werte
- Archivseite für Metadatenfelder (metafield archive)
- Optionen: Anleitung zur Nutzung des Plugins und pflegen von Metadaten 
- Optionen: Anpassung der Darstellung von Metadaten im Frontend
- Optionen: Einstellung der Sprache der Wertelabels
- Frontend: Bibliografische Angaben aus Metadaten erstellen, ggf. auch als BibTex
- Editor: Import/Export von Metadaten im JSON bzw. YAML-Format, siehe: https://liascript.github.io/course/?https://raw.githubusercontent.com/tibhannover/oer-github-tutorial-liascript/main/tutorial.md#8
- Editor: interne Relationen zu anderen Dokumenten erstellen (isPartOf, hasPart)
- Interne Sitemap mit Metadaten ausgezeichneter Dokumente

