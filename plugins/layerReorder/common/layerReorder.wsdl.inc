      <!-- layerReorder -->

      <complexType name="LayerReorderInit">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="layers" type="types:ArrayOfString"/>
        </all>
      </complexType>

      <complexType name="ArrayOfLayerTransparency">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:LayerTransparency[]"/>
          </restriction>
        </complexContent>
      </complexType>

      <complexType name="LayerTransparency">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="id" type="xsd:string"/>
          <element name="transparency" type="xsd:string"/>
        </all>
      </complexType>

      <complexType name="LayerReorderRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="layerIds" type="types:ArrayOfString"/>
          <element name="layerTransparencies" type="types:ArrayOfLayerTransparency"/>
        </all>
      </complexType>

      <complexType name="LayerReorderResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="layers" type="types:ArrayOfString" minOccurs="0"/>
        </all>
      </complexType>
