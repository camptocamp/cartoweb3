      <!-- query -->

      <complexType name="QueryRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="bbox" type="types:Bbox"/>
          <element name="queryAllLayers" type="xsd:boolean"/>
          <element name="defaultMaskMode" type="xsd:boolean"/>
          <element name="defaultTableFlags" type="types:TableFlags"/>
          <element name="querySelections" type="types:ArrayOfQuerySelection"/>
        </all>
      </complexType>

      <complexType name="QueryResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="tableGroup" type="types:TableGroup"/>
        </all>
      </complexType>

      <complexType name="QuerySelection">
        <all>
          <element name="layerId" type="xsd:string"/>
          <element name="idAttribute" type="xsd:string"/>
          <element name="idType" type="xsd:string"/>
          <element name="selectedIds" type="types:ArrayOfString"/>
          <element name="useInQuery" type="xsd:boolean"/>
          <element name="policy" type="xsd:string"/>
          <element name="maskMode" type="xsd:boolean"/>
          <element name="tableFlags" type="types:TableFlags"/>
        </all>
      </complexType>

      <complexType name="ArrayOfQuerySelection">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:QuerySelection[]"/>
          </restriction>
        </complexContent>
      </complexType>
      