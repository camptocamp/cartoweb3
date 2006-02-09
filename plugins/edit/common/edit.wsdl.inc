      <!-- edit -->

      <complexType name="EditRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="shapes" type="types:ArrayOfStyledShape"/>
          <element name="layer" type="xsd:string"/>
          <element name="features" type="types:ArrayOfFeatures"/>
          <element name="featuresIds" type="xsd:string"/>
          <element name="validateAll" type="xsd:boolean"/>
        </all>
      </complexType>

      <complexType name="EditResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="shapeType" type="types:String"/>
          <element name="attributeNames" type="types:ArrayOfString"/>
          <element name="attributeTypes" type="types:ArrayOfString"/>
          <element name="features" type="types:ArrayOfFeatures"/>
        </all>
      </complexType>