      <!-- selection -->

      <complexType name="SelectionRequest">
        <all>
          <element name="policy" type="types:SelectionPolicy"/>
          <element name="bbox" type="types:Bbox"/>
          <element name="layerId" type="xsd:string"/>
          <element name="idAttribute" type="xsd:string"/>
          <!-- should be a struct (string|integer) -->
          <element name="idType" type="xsd:string"/>
          <!-- FIXME: integer should be supported also -->
          <element name="selectedIds" type="types:ArrayOfString"/>
          <element name="maskMode" type="xsd:boolean"/>
          <element name="retrieveAttributes" type="xsd:boolean"/>
          <element name="returnResults" type="xsd:boolean"/>
        </all>
      </complexType>

      <complexType name="SelectionResult">
        <all>
          <element name="selectedIds" type="types:ArrayOfLayerId"/>
          <element name="layerResults" type="types:ArrayOfLayerResult"/>
        </all>      
      </complexType>

      <simpleType name="SelectionPolicy">
        <restriction base="xsd:string">
          <enumeration value="POLICY_XOR"/>
          <enumeration value="POLICY_UNION"/>
          <enumeration value="POLICY_INTERSECTION"/>
        </restriction>
      </simpleType>
      