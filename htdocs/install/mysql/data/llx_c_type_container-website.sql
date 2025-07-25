-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Type of website page/container
--

INSERT INTO llx_c_type_container (code, label, module, active, position, entity) values ('page',     'Page',     'system', 1, 10, __ENTITY__);
INSERT INTO llx_c_type_container (code, label, module, active, position, entity) values ('blogpost', 'BlogPost', 'system', 1, 15, __ENTITY__);
INSERT INTO llx_c_type_container (code, label, module, active, position, entity) values ('menu',     'Menu',     'system', 1, 30, __ENTITY__);
INSERT INTO llx_c_type_container (code, label, module, active, position, entity) values ('banner',   'Banner',   'system', 1, 35, __ENTITY__);
INSERT INTO llx_c_type_container (code, label, module, active, position, entity) values ('other',    'Other',    'system', 1, 40, __ENTITY__);

INSERT INTO llx_c_type_container (code, label, active, module, position, typecontainer, entity) VALUES ('service', 'Web Service (for ajax or api call)', 1, 'system', 300, 'library', __ENTITY__);
INSERT INTO llx_c_type_container (code, label, active, module, position, typecontainer, entity) VALUES ('library', 'Library (functions)',   1, 'system', 400, 'library', __ENTITY__);
INSERT INTO llx_c_type_container (code, label, active, module, position, typecontainer, entity) VALUES ('setup',   'Setup screen',          1, 'system', 500, 'library', __ENTITY__);
