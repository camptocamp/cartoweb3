      <!-- hilight -->

      <complexType name="HilightRequest">
        <all>
          <element name="layerId" type="xsd:string"/>
          <element name="idAttribute" type="xsd:string"/>
          <element name="idType" type="xsd:string"/>
          <!-- FIXME: integer should be supported also -->
          <element name="selectedIds" type="types:ArrayOfString"/>
        </all>
      </complexType>

      <complexType name="HilightResult">
      </complexType>
