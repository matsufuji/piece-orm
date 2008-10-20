# $Id$

######################################################################
# Copyright (c) 2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions are met:
#
#     * Redistributions of source code must retain the above copyright
#       notice, this list of conditions and the following disclaimer.
#     * Redistributions in binary form must reproduce the above copyright
#       notice, this list of conditions and the following disclaimer in the
#       documentation and/or other materials provided with the distribution.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
# AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
# IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
# ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
# LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
# CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
# SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
# INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
# CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
# ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
# POSSIBILITY OF SUCH DAMAGE.
######################################################################

name: piece-orm-mapper.dsl
desc: The Piece_ORM Mapper DSL Schema 2.0.0.
type: map
required: no
mapping:
  "methods":
    type: map
    required: no
    mapping:
      =:
        type: map
        required: no
        mapping:
          "query": { type: str, required: no }
          "orderBy": { type: str, required: no }
          "associations":
            type: map
            required: no
            mapping:
              =:
                type: map
                required: yes
                mapping:
                  "table": { type: str, required: yes }
                  "type": { type: str, required: yes, enum: [ manyToMany, oneToMany, manyToOne, oneToOne ] }
                  "column": { type: str, required: no }
                  "referencedColumn": { type: str, required: no }
                  "through":
                    type: map
                    required: no
                    mapping:
                      "table": { type: str, required: yes }
                      "column": { type: str, required: no }
                      "referencedColumn": { type: str, required: no }
                      "inverseColumn": { type: str, required: no }
                  "orderBy": { type: str, required: no }

# Local Variables:
# mode: conf-colon
# coding: iso-8859-1
# tab-width: 2
# indent-tabs-mode: nil
# End: