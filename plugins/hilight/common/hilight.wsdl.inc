      <!-- hilight -->

      <complexType name="HilightRequest">
        <all>
          <element name="layerId" type="xsd:string"/>
          <element name="idAttribute" type="xsd:string"/>
          <!-- should be a struct (string|integer) -->
          <element name="idType" type="xsd:string"/>
          <!-- FIXME: integer should be supported also -->
          <element name="selectedIds" type="types:ArrayOfString"/>
          <element name="maskMode" type="xsd:boolean"/>
        </all>
      </complexType>

      <complexType name="HilightResult">
      </complexType>

      <complexType name="HilightInit">
        <all>
        </all>      
      </complexType>
      